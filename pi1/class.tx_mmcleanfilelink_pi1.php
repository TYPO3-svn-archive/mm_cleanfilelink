<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 Mike Mitterer <office@bitcon.at>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Plugin 'MM Clean filelink' for the 'mm_cleanfilelink' extension.
 *
 * @author	Mike Mitterer <office@bitcon.at>
 */


require_once(PATH_tslib.'class.tslib_pibase.php');

class tx_mmcleanfilelink_pi1 extends tslib_pibase {
	var $prefixId = 'tx_mmcleanfilelink_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_mmcleanfilelink_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey = 'mm_cleanfilelink';	// The extension key.
	var $pi_checkCHash = TRUE;
	
	var $pObj;
	var	$orig_cObj = null;
	
	function render_uploads($content,$conf)	{
		$out = '';
		
		// Reduce the length of the function-call
		$this->orig_cObj = $this->pObj->cObj;

			// Set layout type:
		$type = intval($this->orig_cObj->data['layout']);

			// Get the list of files (using stdWrap function since that is easiest)
		$lConf = array();
		$lConf['override.']['filelist.']['field'] = 'select_key';
		$fileList = $this->orig_cObj->stdWrap($this->orig_cObj->data['media'],$lConf);

			// Explode into an array:
		$fileArray = t3lib_div::trimExplode(',',$fileList,1);

			// If there were files to list...:
		if (count($fileArray))	{

				// Get the path from which the images came:
			$selectKeyValues = explode('|',$this->orig_cObj->data['select_key']);
			$path = trim($selectKeyValues[0]) ? trim($selectKeyValues[0]) : 'uploads/media/';

				// Get the descriptions for the files (if any):
			$descriptions = t3lib_div::trimExplode(chr(10),$this->orig_cObj->data['imagecaption']);

				// Adding hardcoded TS to linkProc configuration:
			$conf['linkProc.']['path.']['current'] = 1;
			$conf['linkProc.']['icon'] = 1;	// Always render icon - is inserted by PHP if needed.
			$conf['linkProc.']['icon.']['wrap'] = ' | //**//';	// Temporary, internal split-token!
			$conf['linkProc.']['icon_link'] = 1;	// ALways link the icon
			$conf['linkProc.']['icon_image_ext_list'] = ($type==2 || $type==3) ? $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'] : '';	// If the layout is type 2 or 3 we will render an image based icon if possible.

				// Traverse the files found:
			$filesData = array();
			foreach($fileArray as $key => $fileName)	{
				$absPath = t3lib_div::getFileAbsFileName($path.$fileName);
				if (@is_file($absPath))	{
					$fI = pathinfo($fileName);
					$filesData[$key] = array();

					$filesData[$key]['link_part'][0] = '';
					$filesData[$key]['link_part'][1] = '';
					$filesData[$key]['filename'] = $fileName;
					$filesData[$key]['path'] = $path;
					$filesData[$key]['filesize'] = filesize($absPath);
					$filesData[$key]['fileextension'] = strtolower($fI['extension']);
					$filesData[$key]['description'] = trim($descriptions[$key]);

					//$conf['linkProc.']['labelStdWrap.']['checkmike'] = true;
					//$conf['linkProc.']['labelStdWrap.']['data.']['10'] = 'TEXT';
					//$conf['linkProc.']['labelStdWrap.']['data.']['10.']['value'] = $filesData[$key]['description'];
					
					
					$this->orig_cObj->setCurrentVal($path);
					$GLOBALS['TSFE']->register['ICON_REL_PATH'] = $path . $fileName;
					$filesData[$key]['linkedFilenameParts'] = explode('//**//',$this->orig_cObj->filelink($fileName, $conf['linkProc.']));

					preg_match('#(<a[^>]*>).*(</a>)$#',$filesData[$key]['linkedFilenameParts'][1],$filesData[$key]['link_part']);
					
					$conf2['linkProc.'] = $conf['linkProc.'];
					$conf2['linkProc.']['labelStdWrap.']['override'] = $filesData[$key]['description'];
					$filesData[$key]['linkedDescriptionParts'] = explode('//**//',$this->orig_cObj->filelink($fileName, $conf2['linkProc.']));
					
					//labelStdWrap
					//debug($filesData[$key]['linkedFilenameParts'],1);
					//debug($conf['linkProc.'],1);
					//debug("----------------------------------",1);
				}
			}

				// Now, lets render the list!
			$templateBase	= trim($this->orig_cObj->fileResource('EXT:' . $this->extKey . '/pi1/res/' . 'template-type0.tmpl'));
			$template = $templateBase;
			if($type > 0) {
				$template			= trim($this->orig_cObj->fileResource('EXT:' . $this->extKey . '/pi1/res/' . 'template-type' . $type . '.tmpl'));
				if($template == '') $template = $templateBase;
				}
				
			$templateROW 	= trim($this->orig_cObj->getSubpart($template,'###ROW###'));
			
			$tRows = array();
			foreach($filesData as $key => $fileD)	{

					// Setting class of table row for odd/even rows:
				$oddEven = $key%2 ? 'tr-odd' : 'tr-even';
				$templateROWTemp = $templateROW;
				
				unset($markerArray);
				$markerArray['###ROWCLASS###'] 				= $oddEven;
				$markerArray['###ICON###'] 						= $fileD['linkedFilenameParts'][0];
				$markerArray['###DESCRIPTION###'] 		= htmlspecialchars($fileD['description']);
				$markerArray['###LINKDESCRIPTION###']	= $fileD['linkedDescriptionParts'][1];
				$markerArray['###FILENAME###']				= $fileD['filename'];
				$markerArray['###LINKFILENAME###']		= $fileD['linkedFilenameParts'][1];
				$markerArray['###SIZE###']						= t3lib_div::formatSize($fileD['filesize']);
				$markerArray['###LINKBEGIN###']				= $filesData[$key]['link_part'][1];
				$markerArray['###LINKEND###']					= $filesData[$key]['link_part'][2];

				if($type == 0) {
					$templateROWTemp = $this->orig_cObj->substituteSubpart($templateROWTemp,'###ICON_CELL###','');
					}
				if(!$this->orig_cObj->data['filelink_size']) {
					$templateROWTemp = $this->orig_cObj->substituteSubpart($templateROWTemp,'###SIZE_CELL###','');
					} 
				
				$tRows[] = $this->orig_cObj->substituteMarkerArray($templateROWTemp,$markerArray);
				
					// Render row, based on the "layout" setting
				/*
				$tRows[]='
				<tr class="'.$oddEven.'">'.($type>0 ? '
					<td class="csc-uploads-icon">' .
						$fileD['linkedFilenameParts'][0] .
					'</td>' : '') . 
					'<td class="csc-uploads-fileName">' . 
						'<p>' . 
						$fileD['linkedDescriptionParts'][1] . 
						'</p>' . 
						// ($fileD['description'] ? '<p class="csc-uploads-description">' . htmlspecialchars($fileD['description']) . '</p>'	: '') . 
						'</td>'.($this->orig_cObj->data['filelink_size'] ? '
					<td class="csc-uploads-fileSize">
						<p>'.t3lib_div::formatSize($fileD['filesize']).'</p>
					</td>' : '').'
				</tr>';
				*/
			}

				// Table tag params.
			$tableTagParams = $this->pObj->getTableAttributes($conf,$type);
			$tableTagParams['class'] = 'csc-uploads csc-uploads-'.$type;

			$markerArrayTable['###TABLEATTRIBUTES###'] = t3lib_div::implodeAttributes($tableTagParams);
			$template = $this->orig_cObj->substituteMarkerArray($template,$markerArrayTable);
			$out = $this->orig_cObj->substituteSubpart($template,'###ROW###',implode('',$tRows));
			
				// Compile it all into table tags:
				/*
			$out = '
			<table '.t3lib_div::implodeAttributes($tableTagParams).'>
				'.implode('',$tRows).'
			</table>';
			*/
		}

			// Calling stdWrap:
		if ($conf['stdWrap.']) {
			$out = $this->orig_cObj->stdWrap($out, $conf['stdWrap.']);
			}

			// Return value
		return $out;
		}

	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	/*
	function main($content,$conf)	{
		return 'Hello World!<HR>
			Here is the TypoScript passed to the method:'.
					t3lib_div::view_array($conf);
	}
	*/
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mm_cleanfilelink/pi1/class.tx_mmcleanfilelink_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mm_cleanfilelink/pi1/class.tx_mmcleanfilelink_pi1.php']);
}

?>