<?php
include_once "../include/db.php";
include_once "../include/authenticate.php";
# b permission is the bottom collection management lightbox
#if (checkperm("b")){exit("Permission denied");}
# s permission is search functionality
if (!checkperm("s")) {exit ("Permission denied.");}
include_once "../include/resource_functions.php";
include_once "../include/collections_functions.php";
include_once "../include/ui_functions.php";

$curr_page = $baseurl_short."pages/collection_shared.php";

# Get and escape URL parameters
$offset=getvalescaped("offset",0);
$find=getvalescaped("find",getvalescaped("saved_find",""));setcookie("saved_find",$find, 0, '', '', false, true);
$col_order_by=getvalescaped("col_order_by",getvalescaped("saved_col_order_by","created"));setcookie("saved_col_order_by",$col_order_by, 0, '', '', false, true);
$sort=getvalescaped("sort",getvalescaped("saved_col_sort","ASC"));setcookie("saved_col_sort",$sort, 0, '', '', false, true);
$revsort = ($sort=="ASC") ? "DESC" : "ASC";

# Paging
$per_page=getvalescaped("per_page_list",$default_perpage_list,true);setcookie("per_page_list",$per_page, 0, '', '', false, true);

$collection_valid_order_bys=array("name","count");
$modified_collection_valid_order_bys=hook("modifycollectionvalidorderbys");
if ($modified_collection_valid_order_bys){$collection_valid_order_bys=$modified_collection_valid_order_bys;}
# Check the value is one of the valid values (SQL injection filter)
if (!in_array($col_order_by,$collection_valid_order_bys)) {$col_order_by="created";}

if (array_key_exists("find",$_POST)) {$offset=0;} # reset page counter when posting
# End Paging

hook('customcollectionmanage');

include "../../../include/header.php";
?>
  <div class="BasicsBox">
    <h2>&nbsp;</h2>
    <h1><?php echo $lang["managemycollections"]?></h1>
    <p class="tight"><?php echo text("introtext")?></p>
<?php

$collections=user_get_collections($userref,$find,$col_order_by,$sort);

$results=count($collections);

# Paging
$totalpages=ceil($results/$per_page);
$curpage=floor($offset/$per_page)+1;
$jumpcount=1;

# Create an a-z index
$atoz="<div class=\"InpageNavLeftBlock\">";
if ($find=="") {$atoz.="<span class='Selected'>";}
$atoz.="<a href=\"".$curr_page."?col_order_by=name&find=\" onClick=\"return CentralSpaceLoad(this);\">" . $lang["viewall"] . "</a>";
if ($find=="") {$atoz.="</span>";}
$atoz.="&nbsp;&nbsp;&nbsp;&nbsp;";
for ($n=ord("A");$n<=ord("Z");$n++)
	{
	if ($find==chr($n)) {$atoz.="<span class='Selected'>";}
	$atoz.="<a href=\"".$curr_page."?col_order_by=name&find=" . chr($n) . "\" onClick=\"return CentralSpaceLoad(this);\">&nbsp;" . chr($n) . "&nbsp;</a> ";
	if ($find==chr($n)) {$atoz.="</span>";}
	$atoz.=" ";
	}
$atoz.="</div>";

$url=$curr_page."?paging=true&col_order_by=".urlencode($col_order_by)."&sort=".urlencode($sort)."&find=".urlencode($find)."";

	?><div class="TopInpageNav"><?php echo $atoz?> <div class="InpageNavLeftBlock"><?php echo $lang["resultsdisplay"]?>:
  	<?php 
  	for($n=0;$n<count($list_display_array);$n++){?>
  	<?php if ($per_page==$list_display_array[$n]){?><span class="Selected"><?php echo htmlspecialchars($list_display_array[$n]) ?></span><?php } else { ?><a href="<?php echo $url; ?>&per_page_list=<?php echo urlencode($list_display_array[$n])?>" onClick="return CentralSpaceLoad(this);"><?php echo htmlspecialchars($list_display_array[$n]) ?></a><?php } ?>&nbsp;|
  	<?php } ?>
  	<?php if ($per_page==99999){?><span class="Selected"><?php echo $lang["all"]?></span><?php } else { ?><a href="<?php echo $url; ?>&per_page_list=99999" onClick="return CentralSpaceLoad(this);"><?php echo $lang["all"]?></a><?php } ?>
  	</div> <?php pager(false); ?></div><?php	

// count how many collections are owned by the user versus just shared, and show at top
$mycollcount = 0;
$othcollcount = 0;
for($i=0;$i<count($collections);$i++){
	if ($collections[$i]['user'] == $userref){
		$mycollcount++;
	} else {
		$othcollcount++;
	}
}

$collcount = count($collections);
echo $collcount==1 ? $lang["total-collections-1"] : str_replace("%number", $collcount, $lang["total-collections-2"]);
echo " " . ($mycollcount==1 ? $lang["owned_by_you-1"] : str_replace("%mynumber", $mycollcount, $lang["owned_by_you-2"])) . "<br />";
# The number of collections should never be equal to zero.
?>

    <div class="Listview">
        <table border="0" cellspacing="0" cellpadding="0" class="ListviewStyle">
            <tr class="ListviewTitleStyle">
                <!-- Graphic -->

                <!-- Name -->
                <td class="name"><?php if ($col_order_by=="name") {?><span class="Selected"><?php } ?><a href="<?php echo $curr_page ?>?offset=0&col_order_by=name&sort=<?php echo urlencode($revsort)?>&find=<?php echo urlencode($find)?>" onClick="return CentralSpaceLoad(this);"><?php echo $lang["collectionname"]?></a><?php if ($col_order_by=="name") {?><div class="<?php echo urlencode($sort)?>">&nbsp;</div><?php } ?></td>
                <!-- Owner -->
                <?php if (!$collection_public_hide_owner) { ?><td class="fullname"><?php if ($col_order_by=="user") {?><span class="Selected"><?php } ?><a href="<?php echo $curr_page ?>?offset=0&col_order_by=user&sort=<?php echo urlencode($revsort)?>&find=<?php echo urlencode($find)?>" onClick="return CentralSpaceLoad(this);"><?php echo $lang["owner"]?></a><?php if ($col_order_by=="user") {?><div class="<?php echo urlencode($sort)?>">&nbsp;</div><?php } ?></td><?php } ?>
                <!-- Count -->
                <td class="count"><?php if ($col_order_by=="count") {?><span class="Selected"><?php } ?><a href="<?php echo $curr_page ?>?offset=0&col_order_by=count&sort=<?php echo urlencode($revsort)?>&find=<?php echo urlencode($find)?>" onClick="return CentralSpaceLoad(this);"><?php echo $lang["itemstitle"]?></a><?php if ($col_order_by=="count") {?><div class="<?php echo urlencode($sort)?>">&nbsp;</div><?php } ?></td>
                <?php hook("beforecollectiontoolscolumnheader");?>
            </tr>
            <?php

            for ($n=$offset;(($n<count($collections)) && ($n<($offset+$per_page)));$n++)
            {
                ?>
                <tr <?php hook("collectionlistrowstyle");?>>
                    <!-- Image -->
                    <div class="HomePanelPromotedImageWrap">
                        <div style="padding-top:<?php echo floor((155-$collections[$n]["thumb_height"])/2) ?>px;">
                            <img class="ImageBorder" src="<?php echo get_resource_path( $collections[$n]["home_page_image"],false,"thm",false) ?>" width="<?php echo  $collections[$n]["thumb_width"] ?>" height="<?php echo $collections[$n]["thumb_height"] ?>" />
                        </div>
                    </div>
                    <!-- Name -->
                    <td class="name"><div class="ListTitle">
                            <a href="<?php echo $baseurl_short?>pages/search.php?search=<?php echo urlencode("!collection" . $collections[$n]["ref"])?>" onClick="return CentralSpaceLoad(this,true);"><?php echo highlightkeywords(i18n_get_collection_name($collections[$n]),$find)?></a></div></td>
                    <!-- Owner -->
                    <?php if (!$collection_public_hide_owner) { ?><td class="fullname"><?php echo highlightkeywords(htmlspecialchars($collections[$n]["fullname"]),$find)?></td><?php } ?>
                    <!-- Count -->
                    <td class="count"><?php echo $collections[$n]["count"]?></td>
                    <?php hook("beforecollectiontoolscolumn");?>
                <?php } ?>
                </tr>
            <?php
            }
            ?>
        </table>
    </div>
<div class="BottomInpageNav"><?php pager(false); ?></div>
</div>

<?php
include "../include/footer.php";
?>
