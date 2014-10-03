<?php
# Custom ui functions for module
# ui_ functions separate interface logic from user_filtered backend logic

function user_quickbar_update($deleted = '') {
    global $userref, $usercollection;
	# If the user has access to the collection quickbar, update the bar
	if (!checkperm("b")) {
		# User doesn't have quickbar access, no collection selected
		return "";
	}

	# If we deleted a collection, additional work may be necessary
	if($deleted) {
		# Get count of collections
		$c=get_user_collections($userref);
		
		# User has deleted their last collection? add a new one.
		if (count($c)==0) {
			# No collections to select. Create them a new collection.
			$name=get_mycollection_name($userref);
			$usercollection=create_collection ($userref,$name);
			$c=get_user_collections($userref);
		}

		# If the user has just deleted the collection they were using, select a new collection
		if ($usercollection==$deleted) {
			# Select the first collection in the dropdown box.
			$usercollection=$c[0]["ref"];
			set_user_collection($userref,$usercollection);
		}
	}
	
	refresh_collection_frame($usercollection);
}

