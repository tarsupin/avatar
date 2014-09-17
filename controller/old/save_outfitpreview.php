<?php
	require("config.php");
	require("incAVA/dbAVAconnect.php");
	require("incAVA/global.php");

	if (!isset($fetch_avatar['id']))
	{
		header("Location: index.php");
		exit;
	}
	
	define("EXIST", "maysee");
	require("fanctions/check_and_draw.php");
	
	$outfit = get_preview_outfit($fetch_account['account'], $fetch_avatar['base']);
	$base = $fetch_avatar['base'];
	foreach ($outfit as $key => $val)
		if ($val[0] == 0)
		{
			$base = $val[1];
			break;
		}
	$bases = array("white", "pacific", "light", "tan", "dark");
	if (!in_array($base, $bases))
		$base = $fetch_avatar['base'];

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
					$outfit[] = array(0, $base);
			}
			$outfit = wrapper($outfit, $fetch_avatar['gender'], $base, $fetch_account['account'], 0);
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

	$pagetitle = "Get / Use Preview Outfit Code";
	require("incAVA/header.php");
?>
			<div class='category-container'>
				<div class='details-header'>
					Get / Use Preview Outfit Code
				</div>
				<div class='details-body'>
					<table style='width:100%;'>
						<tr>
							<td style='width:205px; vertical-align:top;'>
								<img src='characters/<?php echo $fetch_account['account']; ?>/avi_preview.png?t=<?php echo time(); ?>'/>
							</td>
							<td style='vertical-align:top;'>
								<ul style='text-align:left;'>
									<li>To save a list of what your preview avatar is currently wearing, copy and save the content of the following textbox.</li>
								</ul>
								<textarea rows='5' style='width:100%;' onclick='this.select();'><?php echo serialize($has); ?></textarea>
								<br/><br/>
								<ul style='text-align:left;'>
									<li>To dress your preview avatar in a previously saved outfit, copy the saved code into the following textbox and click the button.</li>
									<li>If the code is old, you may need to reorder items (by Drag & Drop in your <a onclick="window.open('preview_avi.php','PreviewAvatar','width=622,height=500,toolbar=no,location=no,directories=no,status=no,menubar=no,copyhistory=no,scrollbars=yes'); return false;" href='#'>Preview Window</a>) to restore the original look.</li>
									<li>Items that you neither own nor can see in the shops will not equip.</li>
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