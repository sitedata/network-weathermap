<?php

$guest_account = true;

chdir('../../');
require_once "./include/auth.php";

$weathermap_confdir = realpath(dirname(__FILE__) . '/configs');

// include the weathermap class so that we can get the version
require_once dirname(__FILE__)."/lib/Weathermap.class.php";
require_once dirname(__FILE__) . "/lib/database.php";
require_once dirname(__FILE__) . "/lib/WeathermapManager.class.php";

$action = "";
if (isset($_POST['action'])) {
	$action = $_POST['action'];
} else if (isset($_GET['action'])) {
	$action = $_GET['action'];
}

$manager = new WeathermapManager(weathermap_get_pdo(), $weathermap_confdir);

switch($action)
{
case 'viewthumb': // FALL THROUGH
case 'viewimage':
	$id = -1;

	if (isset($_REQUEST['id']) && (!is_numeric($_REQUEST['id']) || strlen($_REQUEST['id'])==20) )
	{
		$id = $manager->translateFileHash($_REQUEST['id']);
	}
	
	if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id']) )
	{
		$id = intval($_REQUEST['id']);
	}
	
	if ($id >= 0) {
		$imageformat = strtolower(read_config_option("weathermap_output_format"));
		
		$userid = (isset($_SESSION["sess_user_id"]) ? intval($_SESSION["sess_user_id"]) : 1);

		$map = $manager->getMapWithAccess($userid, $id);

		if (sizeof($map)) {
			$imagefile = dirname(__FILE__).'/output/'.'/'.$map[0]->filehash.".".$imageformat;
			if ($action == 'viewthumb') {
				$imagefile = dirname(__FILE__) . '/output/' . $map[0]->filehash . ".thumb." . $imageformat;
			}
			$orig_cwd = getcwd();
			chdir(dirname(__FILE__));

			header('Content-type: image/png');
			
			readfile($imagefile);
					
			dir($orig_cwd);	
		} else {
			// no permission to view this map
		}
	}
	
	break;


case 'viewmapcycle':

	$fullscreen = 0;
	if ((isset($_REQUEST['fullscreen']) && is_numeric($_REQUEST['fullscreen'] ) )) {
            $fullscreen = intval($_REQUEST['fullscreen']);
        }
		
	if ($fullscreen==1) {
	    print "<!DOCTYPE html>\n";
		print "<html><head>";
		print '<LINK rel="stylesheet" type="text/css" media="screen" href="cacti-resources/weathermap.css">';		
		print "</head><body id='wm_fullscreen'>";
	} else {
		include_once $config["base_path"]."/include/top_graph_header.php";
	}		

	print "<div id=\"overDiv\" style=\"position:absolute; visibility:hidden; z-index:1000;\"></div>\n";
	print "<script type=\"text/javascript\" src=\"overlib.js\"><!-- overLIB (c) Erik Bosrup --></script> \n";


	$groupid = -1;
	if ((isset($_REQUEST['group']) && is_numeric($_REQUEST['group'] ) )) {
		$groupid = intval($_REQUEST['group']);
	}

	weathermap_fullview(true, false, $groupid, $fullscreen);
	if ($fullscreen == 0) {
		weathermap_versionbox();
	}

	include_once $config["base_path"]."/include/bottom_footer.php";
	break;

case 'viewmap':
	include_once $config["base_path"]."/include/top_graph_header.php";
	print "<div id=\"overDiv\" style=\"position:absolute; visibility:hidden; z-index:1000;\"></div>\n";
	print "<script type=\"text/javascript\" src=\"overlib.js\"><!-- overLIB (c) Erik Bosrup --></script> \n";

	$id = -1;

	if (isset($_REQUEST['id']) && (!is_numeric($_REQUEST['id']) || strlen($_REQUEST['id'])==20) ) {
		$id = $manager->translateFileHash($_REQUEST['id']);
	}

	if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id']) ) {
		$id = intval($_REQUEST['id']);
	}
	
	if ($id >= 0) {
		weathermap_singleview($id);
	}	
	
	weathermap_versionbox();

	include_once $config["base_path"]."/include/bottom_footer.php";
	break;

default:
	include_once $config["base_path"]."/include/top_graph_header.php";
	print "<div id=\"overDiv\" style=\"position:absolute; visibility:hidden; z-index:1000;\"></div>\n";
	print "<script type=\"text/javascript\" src=\"overlib.js\"><!-- overLIB (c) Erik Bosrup --></script> \n";

	$group_id = -1;
	if (isset($_REQUEST['group_id']) && (is_numeric($_REQUEST['group_id']) ) )
	{
		$group_id = intval($_REQUEST['group_id']);
		$_SESSION['wm_last_group'] = $group_id;
	}
	else
	{
		if (isset($_SESSION['wm_last_group']))
		{
			$group_id = intval($_SESSION['wm_last_group']);
		}
	}

	$userid = (isset($_SESSION["sess_user_id"]) ? intval($_SESSION["sess_user_id"]) : 1);
	$tabs = $manager->getTabs($userid);

	$tab_ids = array_keys($tabs);
    if (($group_id == -1) && (sizeof($tab_ids) > 0)) {
        $group_id = $tab_ids[0];
    }

    if (read_config_option("weathermap_pagestyle") == 0) {
        weathermap_thumbview($group_id);
    }
    if (read_config_option("weathermap_pagestyle") == 1) {
        weathermap_fullview(false, false, $group_id);
    }
    if (read_config_option("weathermap_pagestyle") == 2) {
        weathermap_fullview(false, true, $group_id);
    }

	weathermap_versionbox();
	include_once $config["base_path"]."/include/bottom_footer.php";
	break;
}

function weathermap_singleview($mapid)
{
	global $colors;
	global $manager;

	$is_wm_admin = false;

	$outdir = dirname(__FILE__).'/output/';

	$userid = (isset($_SESSION["sess_user_id"]) ? intval($_SESSION["sess_user_id"]) : 1);
	$map = $manager->getMapWithAccess($userid, $mapid);


	if (sizeof($map) > 0) {
 		# print do_hook_function ('weathermap_page_top', array($map[0]['id'], $map[0]['titlecache']) );
 		print do_hook_function ('weathermap_page_top', '' );

		$htmlfile = $outdir.$map[0]->filehash.".html";
		$maptitle = $map[0]->titlecache;
		if ($maptitle == '') {
            $maptitle= "Map for config file: ".$map[0]->configfile;
        }

		weathermap_mapselector($mapid);

		html_graph_start_box(1,true);
?>
<tr bgcolor="<?php print $colors["panel"];?>"><td><table width="100%" cellpadding="0" cellspacing="0"><tr><td class="textHeader" nowrap><?php print $maptitle; 

        if ($is_wm_admin) {
            print "<span style='font-size: 80%'>";
            print "[ <a href='weathermap-cacti88-plugin-mgmt.php?action=map_settings&id=".$mapid."'>Map Settings</a> |";
            print "<a href='weathermap-cacti88-plugin-mgmt.php?action=perms_edit&id=".$mapid."'>Map Permissions</a> |";
            print "<a href=''>Edit Map</a> ]";
            print "</span>";
        }


 ?></td></tr></table></td></tr>
<?php
		print "<tr><td>";

		if (file_exists($htmlfile)) {
			include($htmlfile);
		} else {
			print "<div align=\"center\" style=\"padding:20px\"><em>This map hasn't been created yet.";

			if (weathermap_is_admin()) {
                print " (If this message stays here for more than one poller cycle, then check your cacti.log file for errors!)";
            }
			print "</em></div>";
		}
		print "</td></tr>";
		html_graph_end_box();

	}
}

function weathermap_is_admin()
{
	global $user_auth_realm_filenames;
	global $manager;

	$realm_id = 0;

	if (isset($user_auth_realm_filenames['weathermap-cacti88-plugin-mgmt.php'])) {
		$realm_id = $user_auth_realm_filenames['weathermap-cacti88-plugin-mgmt.php'];
	}
	$userid = (isset($_SESSION["sess_user_id"]) ? intval($_SESSION["sess_user_id"]) : 1);
	$allowed = $manager->checkUserForRealm($userid, $realm_id);

	if ($allowed || (empty($realm_id))) {
		return true;
	}

	return false;
}

function weathermap_show_manage_tab()
{
	global $config;

	if (weathermap_is_admin()) {
		print '<a href="' . $config['url_path'] . 'plugins/weathermap/weathermap-cacti88-plugin-mgmt.php">Manage Maps</a>';
	}
}

function weathermap_thumbview($limit_to_group = -1)
{
	global $colors;
	global $manager;

	$total_map_count = $manager->getMapTotalCount();

	$userid = (isset($_SESSION["sess_user_id"]) ? intval($_SESSION["sess_user_id"]) : 1);

	if ($limit_to_group > 0) {
		$maplist = $manager->getMapsForUser($userid, $limit_to_group);
	} else {
		$maplist = $manager->getMapsForUser($userid);
	}

	// if there's only one map, ignore the thumbnail setting and show it fullsize
	if (sizeof($maplist) == 1) {
		$pagetitle = "Network Weathermap";
		weathermap_fullview(false, false, $limit_to_group);
	} else {
		$pagetitle = "Network Weathermaps";

		html_graph_start_box(2, true);
		?>
		<tr bgcolor="<?php print $colors["panel"]; ?>">
			<td>
				<table width="100%" cellpadding="0" cellspacing="0">
					<tr>
						<td class="textHeader" nowrap> <?php print $pagetitle; ?></td>
						<td align="right"><a href="?action=viewmapcycle">automatically cycle</a> between full-size maps)
						</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td><i>Click on thumbnails for a full view (or you can <a href="?action=viewmapcycle">automatically
						cycle</a> between full-size maps)</i></td>
		</tr>
		<?php
		html_graph_end_box();

		weathermap_tabs($limit_to_group);
		$i = 0;
		if (sizeof($maplist) > 0) {

			$outdir = dirname(__FILE__) . '/output/';

			$imageformat = strtolower(read_config_option("weathermap_output_format"));

			html_graph_start_box(1, false);
			print "<tr><td class='wm_gallery'>";
			foreach ($maplist as $map) {
				$i++;

				$imgsize = "";
				$thumbfile = $outdir . $map->filehash . ".thumb." . $imageformat;
				$thumburl = "?action=viewthumb&id=" . $map->filehash . "&time=" . time();
				if ($map->thumb_width > 0) {
					$imgsize = ' width="' . $map->thumb_width . '" height="' . $map->thumb_height . '" ';
				}
				$maptitle = $map->titlecache;
				if ($maptitle == '') {
					$maptitle = "Map for config file: " . $map->configfile;
				}

				print '<div class="wm_thumbcontainer" style="margin: 2px; border: 1px solid #bbbbbb; padding: 2px; float:left;">';
				if (file_exists($thumbfile)) {
					print '<div class="wm_thumbtitle" style="font-size: 1.2em; font-weight: bold; text-align: center">' . $maptitle . '</div><a href="weathermap-cacti88-plugin.php?action=viewmap&id=' . $map->filehash . '"><img class="wm_thumb" ' . $imgsize . 'src="' . $thumburl . '" alt="' . $maptitle . '" border="0" hspace="5" vspace="5" title="' . $maptitle . '"/></a>';
				} else {
					print "(thumbnail for map not created yet)";
				}

				print '</div> ';
			}
			print "</td></tr>";
			html_graph_end_box();
		} else {
			print "<div align=\"center\" style=\"padding:20px\"><em>You Have No Maps</em>";

			if ($total_map_count == 0) {
				print '<p>To add a map to the schedule, go to the <a href="weathermap-cacti88-plugin-mgmt.php">Manage...Weathermaps page</a> and add one.</p>';
			}
			print "</div>";
		}
	}
}

function weathermap_fullview($cycle=FALSE, $firstonly=FALSE, $limit_to_group = -1, $fullscreen = 0)
{
	global $colors;
	global $manager;

	$_SESSION['custom']=false;
	$userid = (isset($_SESSION["sess_user_id"]) ? intval($_SESSION["sess_user_id"]) : 1);

	if ($limit_to_group >0) {
		$maplist = $manager->getMapsForUser($userid, $limit_to_group);
	} else {
		$maplist = $manager->getMapsForUser($userid);
	}

// TODO deal with this
//	if ($firstonly) {
//		$maplist_SQL .= " LIMIT 1";
//	}

	if (sizeof($maplist) == 1) {
		$pagetitle = "Network Weathermap";
	} else {
		$pagetitle = "Network Weathermaps";
	}
	$class = "";
	if ($cycle) {
		$class = "inplace";
	}
	if ($fullscreen) {
		$class = "fullscreen";
	}

	if ($cycle) {
        print "<script src='vendor/jquery.min.js'></script>";
        print "<script src='vendor/idle-timer.min.js'></script>";
        $extra = "";
        if ($limit_to_group > 0) {
			$extra = " in this group";
		}
		?>
					<div id="wmcyclecontrolbox" class="<?php print $class ?>">
						<div id="wm_progress"></div>
						<div id="wm_cyclecontrols">
						<a id="cycle_stop" href="?action="><img src="cacti-resources/img/control_stop_blue.png" width="16" height="16" /></a>
						<a id="cycle_prev" href="#"><img src="cacti-resources/img/control_rewind_blue.png" width="16" height="16" /></a>
						<a id="cycle_pause" href="#"><img src="cacti-resources/img/control_pause_blue.png" width="16" height="16" /></a>
						<a id="cycle_next" href="#"><img src="cacti-resources/img/control_fastforward_blue.png" width="16" height="16" /></a>
						<a id="cycle_fullscreen" href="?action=viewmapcycle&fullscreen=1&group=<?php echo $limit_to_group; ?>"><img src="cacti-resources/img/arrow_out.png" width="16" height="16" /></a>
						Showing <span id="wm_current_map">1</span> of <span id="wm_total_map">1</span>.
						Cycling all available maps<?php echo $extra; ?>.
						</div>
					</div>
				<?php
			}

	// only draw the whole screen if we're not cycling, or we're cycling without fullscreen mode
	if ($cycle == false || $fullscreen==0) {
		html_graph_start_box(2,true);
?>
			<tr bgcolor="<?php print $colors["panel"];?>">
				<td>
					<table width="100%" cellpadding="0" cellspacing="0">
							<tr>
							   	<td class="textHeader" nowrap> <?php print $pagetitle; ?> </td>
								<td align = "right">
                        <?php if (!$cycle) { ?>
                        (automatically cycle between full-size maps (<?php

                                if ($limit_to_group > 0) {

                                    print '<a href = "?action=viewmapcycle&group='.intval($limit_to_group).'">within this group</a>, or ';
                                }
                                print ' <a href = "?action=viewmapcycle">all maps</a>';
                            ?>)

                        <?php
                        }

                        ?>
                    			</td>
							</tr>
					</table>
				</td>
			</tr>
<?php
		html_graph_end_box();

		weathermap_tabs($limit_to_group);
	}

	$i = 0;
	if (sizeof($maplist) > 0) {
		print "<div class='all_map_holder $class'>";

		$outdir = dirname(__FILE__).'/output/';
		$confdir = dirname(__FILE__).'/configs/';
		foreach ($maplist as $map)
		{
			if ($firstonly && $i > 0) {
				break;
			}
			$i++;
			$htmlfile = $outdir.$map->filehash.".html";
			$maptitle = $map->titlecache;
			if ($maptitle == '') {
				$maptitle = "Map for config file: " . $map->configfile;
			}
			print '<div class="weathermapholder" id="mapholder_'.$map->filehash.'">';
			if ($cycle == false || $fullscreen==0) {
				html_graph_start_box(1,true);

?>
		<tr bgcolor="#<?php echo $colors["header_panel"] ?>">
				<td colspan="3">
						<table width="100%" cellspacing="0" cellpadding="3" border="0">
								<tr>
									<td align="left" class="textHeaderDark">
                                    	<a name="map_<?php echo $map->filehash; ?>">
                                        </a><?php print htmlspecialchars($maptitle); ?>
                                    </td>
								</tr>
						</table>
				</td>
		</tr>
		<tr>
			<td>
<?php
			}

			if (file_exists($htmlfile)) {
				include($htmlfile);
			} else {
				print "<div align=\"center\" style=\"padding:20px\"><em>This map hasn't been created yet.</em></div>";
			}

			if ($cycle == false || $fullscreen==0) {
				print '</td></tr>';
				html_graph_end_box();
			}
			print '</div>';
		}
		print "</div>";

		if ($cycle) {
			$refreshtime = read_config_option("weathermap_cycle_refresh");
			$poller_cycle = read_config_option("poller_interval");
?>
		<script type="text/javascript" src="cacti-resources/map-cycle.js"></script>
		<script type = "text/javascript">
			$(document).ready( function() {
				WMcycler.start({ fullscreen: <?php echo ($fullscreen ? "1" : "0"); ?>,
				    poller_cycle: <?php echo $poller_cycle * 1000; ?>,
				    period: <?php echo $refreshtime  * 1000; ?>});
			});
		</script>
<?php
		}
	}
	else
	{
		print "<div align=\"center\" style=\"padding:20px\"><em>You Have No Maps</em></div>\n";
	}
}

function weathermap_versionbox()
{
	global $WEATHERMAP_VERSION, $colors;

	$pagefoot = "Powered by <a href=\"http://www.network-weathermap.com/?v=$WEATHERMAP_VERSION\">PHP Weathermap version $WEATHERMAP_VERSION</a>";
	
	if (weathermap_is_admin())
	{
		$pagefoot .= " --- <a href='weathermap-cacti88-plugin-mgmt.php' title='Go to the map management page'>Weathermap Management</a>";
		$pagefoot .= " | <a target=\"_blank\" href=\"docs/\">Local Documentation</a>";
		$pagefoot .= " | <a target=\"_blank\" href=\"weathermap-cacti88-plugin-editor.php\">Editor</a>";
	}

	html_graph_start_box(1,true);

?>
<tr bgcolor="<?php print $colors["panel"];?>">
	<td>
		<table width="100%" cellpadding="0" cellspacing="0">
			<tr>
			   <td class="textHeader" nowrap> <?php print $pagefoot; ?> </td>
			</tr>
		</table>
	</td>
</tr>
<?php
	html_graph_end_box();
}



function weathermap_footer_links()
{
	global $colors;
	global $WEATHERMAP_VERSION;
	print '<br />'; 
    html_start_box("<center><a target=\"_blank\" class=\"linkOverDark\" href=\"docs/\">Local Documentation</a> -- <a target=\"_blank\" class=\"linkOverDark\" href=\"http://www.network-weathermap.com/\">Weathermap Website</a> -- <a target=\"_target\" class=\"linkOverDark\" href=\"weathermap-cacti88-plugin-editor.php?plug=1\">Weathermap Editor</a> -- This is version $WEATHERMAP_VERSION</center>", "78%", $colors["header"], "2", "center", "");
	html_end_box(); 
}

function weathermap_mapselector($current_id = 0)
{
	global $colors;
	global $manager;
	
    $show_selector = intval(read_config_option("weathermap_map_selector"));

	if ($show_selector == 0) {
        return false;
    }

	$userid = (isset($_SESSION["sess_user_id"]) ? intval($_SESSION["sess_user_id"]) : 1);
    $maps = $manager->getMapsWithAccessAndGroups($userid);

	if (sizeof($maps)>1) {
		/* include graph view filter selector */
		html_graph_start_box(3, TRUE);
		?>
	<tr bgcolor="<?php print $colors["panel"];?>" class="noprint">
			<form name="weathermap_select" method="post" action="">
			<input name="action" value="viewmap" type="hidden">
			<td class="noprint">
					<table width="100%" cellpadding="0" cellspacing="0">
							<tr class="noprint">
									<td nowrap style='white-space: nowrap;' width="40">
										&nbsp;<strong>Jump To Map:</strong>&nbsp;
									</td>
									<td>
										<select name="id">
<?php

		$ngroups = 0;
		$lastgroup = "------lasdjflkjsdlfkjlksdjflksjdflkjsldjlkjsd";
		foreach ($maps as $map)
		{
			if ($current_id == $map->id) {
			    $nullhash = $map->filehash;
            }
			if ($map->name != $lastgroup)
			{
				$ngroups++;
				$lastgroup = $map->name;
			}
		}


		$lastgroup = "------lasdjflkjsdlfkjlksdjflksjdflkjsldjlkjsd";
		foreach ($maps as $map) {
			if ($ngroups>1 && $map->name != $lastgroup) {
				print "<option style='font-weight: bold; font-style: italic' value='$nullhash'>".htmlspecialchars($map->name)."</option>";
				$lastgroup = $map->name;
			}
			print '<option ';
			if ($current_id == $map->id) {
                print " SELECTED ";
            }
			print 'value="'.$map->filehash.'">';
			// if we're showing group headings, then indent the map names
			if ($ngroups > 1) {
                print " - ";
            }
			print htmlspecialchars($map->titlecache).'</option>';
		}
?>
										</select>
											&nbsp;<input type="image" src="../../images/button_go.gif" alt="Go" border="0" align="absmiddle">										
									</td>
							</tr>
					</table>
			</td>
			</form>
	</tr>
	<?php

		html_graph_end_box(FALSE);
	}
}


function weathermap_tabs($current_tab)
{
	global $colors;
	global $manager;

	// $current_tab=2;
	$userid = (isset($_SESSION["sess_user_id"]) ? intval($_SESSION["sess_user_id"]) : 1);

	$tabs = $manager->getTabs($userid);

	if (sizeof($tabs) > 1) {
		/* draw the categories tabs on the top of the page */
        print "<p></p><table class='tabs' width='100%' cellspacing='0' cellpadding='3' align='center'><tr>\n";

        if (sizeof($tabs) > 0) {
			$show_all = intval(read_config_option("weathermap_all_tab"));
			if ($show_all == 1) {
				$tabs['-2'] = "All Maps";
			}

	        foreach (array_keys($tabs) as $tab_short_name) {
	                print "<td " . (($tab_short_name == $current_tab) ? "bgcolor='silver'" : "bgcolor='#DFDFDF'") . " nowrap='nowrap' width='" . (strlen($tabs[$tab_short_name]) * 9) . "' align='center' class='tab'>
	                                <span class='textHeader'><a href='weathermap-cacti88-plugin.php?group_id=$tab_short_name'>$tabs[$tab_short_name]</a></span>
	                                </td>\n
	                                <td width='1'></td>\n";
	        }

        }

        print "<td></td>\n</tr></table>\n";
		
		return(true);
	} else {
		return (false);
	}
}

// vim:ts=4:sw=4:
?>
