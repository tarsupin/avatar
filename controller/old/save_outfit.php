<?php
	require("config.php");
	require("incAVA/dbAVAconnect.php");
	require("incAVA/global.php");

	if (!isset($fetch_avatar['id']))
	{
		header("Location: index.php");
		exit;
	}
	
	define("EXIST", "own");
	require("fanctions/check_and_draw.php");
		
	$outfit = get_outfit($fetch_avatar['id'], $fetch_avatar['base']);

	if (isset($_POST['changeme']) && isset($_POST['changeinto']))
	{
		if ($wants = unserialize(trim($_POST['changeinto'])))
		{
			$outfit = array();			
			foreach ($wants as $key => $val)
			{
				if ($val[0] != 0)
					$outfit[] = array($val[0], $val[1]);
				else
					$outfit[] = array(0, $fetch_avatar['base']);
			}
			$outfit = wrapper($outfit, $fetch_avatar['gender'], $fetch_avatar['base'], $fetch_account['account'], $fetch_avatar['id']);
			$fetch_avatar['last_timestamp'] = time();
		}
	}

	$has = array();
	foreach ($outfit as $o)
	{
		if ($o[0] > 0)
			$has[] = $o;
		else
			$has[] = array(0, $base);
	}

	$pagetitle = "Get / Use Outfit Code";
	require("incAVA/header.php");
?>
			<div class='category-container'>
				<div class='details-header'>
					Get / Use Outfit Code
				</div>
				<div class='details-body'>
					<table style='width:100%;'>
						<tr>
							<td style='width: 205px; vertical-align:top;'>
								<img src='characters/<?php echo $fetch_account['account']; ?>/avi_<?php echo ($fetch_avatar['id'] + 0); ?>.png?t=<?php echo $fetch_avatar['last_timestamp']; ?>'/>
							</td>
							<td style='vertical-align:top;'>
								<ul style='text-align:left;'>
									<li>To save a list of what your avatar is currently wearing, copy and save the content of the following textbox.</li>
								</ul>
								<textarea rows='5' style='width:100%;' onclick='this.select();'><?php echo serialize($has); ?></textarea>
								<br/><br/>
								<ul style='text-align:left;'>
									<li>To dress your current avatar in a previously saved outfit, copy the saved code into the following textbox and click the button.</li>
									<li>If the code is old, you may need to reorder items (by Drag & Drop in your <a href='dress_avatar.php'>Dressing Room</a>) to restore the original look.</li>
									<li>Items that you do not own will not equip.</li>
								</ul>
								<form method='post'>
									<textarea rows='5' style='width:100%;' name='changeinto'></textarea>
									<input type='submit' name='changeme' value='Replace'/>
								</form>
							</td>
						</tr>
					</table>
				</div>
			</div>
<?php
	require("incAVA/footer.php");
?>