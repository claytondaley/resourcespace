<? 
global $imagemagick_path,$imagemagick_preserve_profiles,$imagemagick_quality,$pdf_pages;

$file=myrealpath(get_resource_path($ref,"",false,$extension)); 

# Set up ImageMagick 
putenv("MAGICK_HOME=" . $imagemagick_path); 
putenv("DYLD_LIBRARY_PATH=" . $imagemagick_path . "/lib"); 
putenv("PATH=" . $ghostscript_path . ":" . $imagemagick_path . ":" . 
$imagemagick_path . "/bin"); # Path 
sql_query("update resource set has_image=0 where ref='$ref'"); 


# Set up target file
$target=myrealpath(get_resource_path($ref,"",false,"jpg")); 
if (file_exists($target)) {unlink($target);}

hook("metadata");

/* ----------------------------------------
	Try InDesign
   ----------------------------------------
*/
# Note: for good results, InDesign Preferences must be set to save Preview image at Extra Large size.
if ($extension=="indd")
	{
#This is how to use exiftool to extract InDesign thumbnails:
	global $exiftool_path;
	if (isset($exiftool_path))
	{
	shell_exec($exiftool_path.'/exiftool -ScanforXMP -f -ThumbnailsImage -b '.$file.' > '.$target);
	}
#Or the old way:	
 	else {
	$indd_thumb = extract_indd_thumb ($file);
	if ($indd_thumb!="no")
		{
		base64_to_jpeg( $indd_thumb, $target);
		if (file_exists($target)){$newfile = $target;}
		}
		}
		
	hook("indesign");	
	}

/* ----------------------------------------
	Try OpenDocument Format
   ----------------------------------------
*/

if (($extension=="odt") || ($extension=="ods")  || ($extension=="odp")) 
	{
shell_exec("unzip -p $file \"Thumbnails/thumbnail.png\" > $target");
    $command=$imagemagick_path . "/bin/convert";
    if (!file_exists($command)) {$command=$imagemagick_path . "/convert";}
    if (!file_exists($command)) {$command=$imagemagick_path . "\convert.exe";}
    if (!file_exists($command)) {exit("Could not find ImageMagick 'convert' utility. $command'");}	
$command=$command . " \"$target\"[0]  \"$target\""; 
				$output=shell_exec($command); 
	}


/* ----------------------------------------
	Try text file to JPG conversion
   ----------------------------------------
*/
# Support text files simply by rendering them on a JPEG.
if ($extension=="txt")
	{
	$text=wordwrap(file_get_contents($file),90);
	$width=600;$height=800;
	$font="gfx/fonts/vera.ttf";
	$im=imagecreatetruecolor($width,$height);
	$col=imagecolorallocate($im,255,255,255);
	imagefilledrectangle($im,0,0,$width,$height,$col);
	$col=imagecolorallocate($im,0,0,0);
	imagettftext($im,9,0,10,25,$col,$font,$text);
    imagejpeg($im,$target);
	$newfile=$target;
	}


/* ----------------------------------------
	Try FFMPEG for video files
   ----------------------------------------
*/
global $ffmpeg_path; 
if (isset($ffmpeg_path) && !isset($newfile)) 
        { 
         $command=$ffmpeg_path . "/ffmpeg -i \"$file\" -f image2 -t 0.001 -ss 1 \"" . $target . "\""; 
         
         if ($extension=="mxf")
         	$command=$ffmpeg_path . "/ffmpeg -i \"$file\" -f image2 -t 0.001 -ss 0 \"" . $target . "\""; 
         
        $output=shell_exec($command); 
        #exit($command . "<br>" . $output); 
        if (file_exists($target)) 
            {
            $newfile=$target;
            
            global $ffmpeg_preview,$ffmpeg_preview_seconds;
            if ($ffmpeg_preview)
                {
                # Create a preview video (FLV)
                $targetfile=myrealpath(get_resource_path($ref,"",false,"flv")); 
                $command=$ffmpeg_path . "/ffmpeg -i \"$file\" -f flv -ar 22050 -b 650k -ab 32 -ac 1 -s 480x270 -t $ffmpeg_preview_seconds  \"$targetfile\"";
                $output=shell_exec($command); 
                }
            } 
        } 


/* ----------------------------------------
	Try ImageMagick
   ----------------------------------------
*/
if (!isset($newfile))
	{
	# Locate imagemagick.
    $command=$imagemagick_path . "/bin/convert";
    if (!file_exists($command)) {$command=$imagemagick_path . "/convert";}
    if (!file_exists($command)) {$command=$imagemagick_path . "\convert.exe";}
    if (!file_exists($command)) {exit("Could not find ImageMagick 'convert' utility. $command'");}	

    $prefix="";

	# Preserve colour profiles?    
	$profile="+profile icc -colorspace RGB"; # By default, strip the colour profiles ('+' is remove the profile, confusingly)
    if ($imagemagick_preserve_profiles) {$profile="";}
    
    # CR2 files need a cr2: prefix
    if ($extension=="cr2") {$prefix="cr2:";}
    
   if (($extension=="pdf") || ($extension=="eps") || ($extension=="ps")) 
    	{
   	    # For EPS/PS/PDF files, use GS directly and allow multiple pages.
		# EPS files are always single pages:
		if ($extension=="eps") {$pdf_pages=1;}

		# Locate ghostscript command
		$gscommand= $ghostscript_path. "/gs";
	    if (!file_exists($gscommand)) {$gscommand= $ghostscript_path. "\gs.exe";}
        if (!file_exists($gscommand)) {exit("Could not find GhostScript 'gs' utility.'");}	
        
		# Create multiple pages.
		for ($n=1;$n<=$pdf_pages;$n++)
			{
			# Set up target file
			$size="";if ($n>1) {$size="scr";} # Use screen size for other pages.
			$target=myrealpath(get_resource_path($ref,$size,false,"jpg",-1,$n)); 
			if (file_exists($target)) {unlink($target);}
			
			$gscommand2 = $gscommand . " -dBATCH -dNOPAUSE -sDEVICE=jpeg -sOutputFile=\"$target\" -dFirstPage=" . $n . " -dLastPage=" . $n . " -dUseCropBox -dEPSCrop \"$file\"";
 			$output=shell_exec($gscommand2); 
	
			# Set that this is the file to be used.
			if (file_exists($target) && $n==1)
				{
				$newfile=$target;
				}
			
			# For files other than page 1, resize directly to the screen size (no other sizes needed)
			if (file_exists($target) && $n>1)
				{
				$command2=$command . " " . $prefix . "\"$target\"[0] -quality $imagemagick_quality -resize 800x800 \"$target\""; 
				$output=shell_exec($command2); 
				
				# Add a watermarked image too?
				global $watermark;
    			if (isset($watermark))
    				{
					$path=myrealpath(get_resource_path($ref,$size,false,"",-1,$n,true));
					if (file_exists($path)) {unlink($path);}
    				$watermarkreal=myrealpath($watermark);
    				
				    $command2 = $command . " \"$target\"[0] $profile -quality $imagemagick_quality -resize 800x800 -tile $watermarkreal -draw \"rectangle 0,0 800,800\" \"$path\""; 
					$output=shell_exec($command2); 
					}
				
				}
			}
		}
    else
    	{
    	# Not a PDF file, so single extraction only.
		$command.= " " . $prefix . "\"$file\"[0] $profile -quality $imagemagick_quality -resize 800x800 \"$target\""; 
		$output=shell_exec($command); 
		if (file_exists($target))
			{
			$newfile=$target;
			}
		}
	}
	

# If a file has been created, generate previews just as if a JPG was uploaded.
if (isset($newfile))
	{
	create_previews($ref,false,"jpg");	
	}
extract_exif_comment($ref,$extension);	
?>
