<?php

//Deny direct initialization for extra security
if(!defined("IN_MYBB")) {
    die("You Cannot Access This File Directly. Please Make Sure IN_MYBB Is Defined.");
}

//Hooks
$plugins->add_hook("xmlhttp", "clwo_staff_assigner_xmlhttp");

//Plugin information
function clwo_staff_assigner_info() {
    clwo_staff_assigner_f_debug("Primitive function: info()");

    return array(
        "name"  => "CLWO Staff Assigner",
        "description"=> 'This plugin sets the staff ranks of CLWO staff. <br>
        <b>NOTE</b> This plugin relies on the steam_activation plugin to be active. <br>
        This plugin is made to be called by Popey/Sourcecode.',
        "website"        => "https://clwo.eu",
        "author"        => "Square Play'n",
        "authorsite"    => "http://squareplayn.com",
        "version"        => "1.2",
        "guid"             => "",
        "compatibility" => "18*"
    );
}

//Return if plugin is installed
function clwo_staff_assigner_is_installed() {
    clwo_staff_assigner_f_debug("Primitive function: is_installed()");
    global $mybb;

    //Look for one of the settings and see if it exists
    return isset($mybb->settings["clwo_staff_assigner_pleb_group"]);
}

//Installation procedure for plugin
function clwo_staff_assigner_install() {
    clwo_staff_assigner_f_debug("Primitive function: install()");
    global $db;

    /****** SETTINGS *******/

    //Setup settings group
    clwo_staff_assigner_f_debug("Adding the settings group to table:settinggroups");
    $settings_group = array(
        "gid"    		=> "NULL",
        "name" 		 	=> "clwo_staff_assigner",
        "title"      	=> "CLWO Staff Assigner",
        "description"   => "Settings For the CLWO Staff Assigner Plugin",
        "disporder"    	=> "1",
        "isdefault"  	=> "no",
    );
    $gid = $db->insert_query("settinggroups", $settings_group);

    //All the actual settings fields
    clwo_staff_assigner_f_debug("Initializing all settings");

    $settings_array = array(
        "clwo_staff_assigner_pleb_group" => array(
            "title"			=> "Non-staff users group",
            "description"	=> "Select the default group that users are when they are not staff. <br> 
	           When users had a different rank before or aside of their staff rank, they will become that rank instead of the rank you select here.",
            "optionscode"  	=> "groupselectsingle",
            "value"       	=> "5"
        ),

        "clwo_staff_assigner_custom_group" => array(
            "title"			=> "Customly named users group",
            "description"	=> "This group will be ignored by this plugin regarding setting the displayed name. Whenever you change someones name to something custom,<br/>
                                Set their main group to this group and add any other groups you want the person to have as additional user groups.",
            "optionscode"  	=> "groupselectsingle",
            "value"       	=> "6"
        ),

        "clwo_staff_assigner_groups" => array(
            "title"			=> "All settable group for each server",
            "description"	=> "In the beneath textfield, you can define which ranks can be set and by what post variable they are called.<br>
                                Do so by putting after each other. Trailing ; is allowed.<br/>
                                groupid-postvariable; <br/>
                                For example: <br/>
                                1-ttt_staff;2-jb_staff",
            "optionscode"  	=> "text",
            "value"       	=> ""
        ),

        "clwo_staff_assigner_code" => array(
            "title"			=> "All settable group for each server",
            "description"	=> "Whenever a call is made to xmlhttp.php?staff_rank_change=true it checks for if the POST variable code is set to this string" ,
            "optionscode"  	=> "text",
            "value"       	=> "ChangeThisCode!"
        ),
    );

    //Add all the settings to the database
    clwo_staff_assigner_f_debug("Looping through every setting");
    $disporder = 1;
    foreach($settings_array as $name => $setting) {
        clwo_staff_assigner_f_debug("Adding setting ".$name." to table:settings");
        $setting["name"] = $name;
        $setting["gid"] = $gid;
        $setting["disporder"] = $disporder;
        $disporder++;
        $db->insert_query("settings", $setting);
    }

    //Update the settings file
    clwo_staff_assigner_f_debug("Updating the settings pages");
    rebuild_settings();
}

//Uninstall procedure for plugin
function clwo_staff_assigner_uninstall() {
    clwo_staff_assigner_f_debug("Primitive function: uninstall()");
    global $db;

    //Clean up settings
    clwo_staff_assigner_f_debug("Deleting the settings of this plugin from tables:settings&settinggroups");
    $db->delete_query("settings", "name LIKE ('clwo_staff_assigner_%')");
    $db->delete_query("settinggroups", "name='clwo_staff_assigner'");

    clwo_staff_assigner_f_debug("Updating the settings pages");
    rebuild_settings();
}

//Activation procedure for plugin
function clwo_staff_assigner_activate() {
    clwo_staff_assigner_f_debug("Primitive function: activate()");

}

//Deactivation procedure for plugin
function clwo_staff_assigner_deactivate() {
    clwo_staff_assigner_f_debug("Primitive function: deactivate()");

}

/********* Hooks *********************/


//Hook xmlhttp
function clwo_staff_assigner_xmlhttp() {
    global $mybb, $db;
    if(isset($mybb->input["staff_rank_change"])) {
        $message = "No error message was set";
        $status = 400;

        //Check many conditions. See message for failed version for explanation
        if(isset($mybb->input["code"])){
            if($mybb->input["code"] === $mybb->settings["clwo_staff_assigner_code"]) {
                if(isset($mybb->input["steamid"])) {
                    if(is_numeric($mybb->input["steamid"])) {

                        //Get the user information from the DB using the given steamid
                        $steamid = $db->escape_string($mybb->input["steamid"]);
                        $query = $db->simple_select("users", "*", "steam_activation_steamid='".$steamid."'");
                        if($db->num_rows($query) > 0) {

                            $user = $db->fetch_array($query);
                            $maingroup = $user["usergroup"];
                            echo("Found maingroup: ".$maingroup."<br>");
                            echo("Setting = ".$mybb->settings["clwo_staff_assigner_custom_group"].".<br>");
                            $groups = explode(",", $user["additionalgroups"]);

                            //Initialize new ranks with the old ones to not influence groups that have noting to do with this plugin
                            $newRanks = array();
                            foreach ($groups as $group) {
                                $newRanks[$group] = true;
                            }

                            if(isset($newRanks[$mybb->settings["clwo_staff_assigner_custom_group"]])) {
                                $titleprotected = true;
                            } else {
                                $titleprotected = false;
                            }

                            //Read each value to see if the ranks should be set to true or false
                            $error = false;
                            $checkgroups = explode(";", rtrim($mybb->settings["clwo_staff_assigner_groups"], ";"));
                            foreach ($checkgroups as $checkgroup) {
                                $info = explode("-", $checkgroup);
                                $gid = $info[0];
                                $postvariable = $info[1];
                                //echo("Checking variable ".$postvariable." to see if we should set gid ".$gid.".<br>");
                                if(isset($mybb->input[$postvariable])) {
                                    if($mybb->input[$postvariable] === "true") {
                                        $newRanks[$gid] = true;
                                        $message = "Yaay, rank true";
                                    } else {
                                        $newRanks[$gid] = false;
                                        $message = "Aww, rank false";
                                    }
                                } else {
                                    $message = "Did not receive value for variable ".$postvariable;
                                    $error = true;
                                    break;
                                }
                            }

                            //If no error occured during the checking of all variables
                            if(!$error) {
                                $updates = array(); //Stores all values of fields that should be updated

                                //Check if the title needs a change
                                if(!$titleprotected) {
                                    if ($maingroup != $mybb->settings["clwo_staff_assigner_custom_group"]) {
                                        if (isset($mybb->input["title"])) {
                                            $updates["usertitle"] = $mybb->input["title"];
                                        } else {
                                            $updates["usertitle"] = null;
                                        }
                                    }
                                }

                                //Check for change of maingroup
                                if(isset($newRanks[$maingroup]) && $newRanks[$maingroup] == false) {
                                    $updates["usergroup"] = $mybb->settings["clwo_staff_assigner_pleb_group"];
                                }

                                //Build the new additionalgroups
                                $updates["additionalgroups"] = "";
                                foreach ($newRanks as $gid=>$rank) {
                                    if($rank) {
                                        $updates["additionalgroups"] .= $gid.",";
                                    }
                                }
                                $updates["additionalgroups"] = rtrim($updates["additionalgroups"],",");

                                //Escape strings
                                foreach ($updates as $key => $value) {
                                    $updates[$key] = $db->escape_string($value);
                                }

                                //Send it off to the database
                                if($db->update_query("users", $updates, "uid=".$user["uid"])) {
                                    $message = "Update successful";
                                    $status = 200;
                                } else {
                                    $message = "Unknown update query error";
                                }
                            }
                        } else {
                            $message = "No forum user found for the given steamid";
                        }
                    } else {
                        $message = "The variable provided as steamid is not a valid steamid64";
                    }
                } else {
                    $message = "No steamid set";
                }
            } else {
                $message = "Invalid code";
            }
        } else {
            $message = "please provide a valid code";
        }


        $returnarray = array(
            "date" => time(),
            "status" => $status,
            "message" => $message
        );
        echo(json_encode($returnarray));
        die;
    }
}

/********* Other functions ****************/

//Can be used (hardcoded) to enable debug messages
function clwo_staff_assigner_f_debug($message) {
    if(true) { //Set to true to enable, false to disable
        echo("<b>[clwo_staff_assigner]</b>".$message."<br>");
    }
}
