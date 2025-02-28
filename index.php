<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

/**
 * xmnews module
 *
 * @copyright       XOOPS Project (https://xoops.org)
 * @license         GNU GPL 2 (http://www.gnu.org/licenses/old-licenses/gpl-2.0.html)
 * @author          Mage Gregory (AKA Mage)
 */

use \Xmf\Request;
use \Xmf\Metagen;

include_once __DIR__ . '/header.php';
$GLOBALS['xoopsOption']['template_main'] = 'xmnews_index.tpl';
include_once XOOPS_ROOT_PATH . '/header.php';

$xoTheme->addStylesheet(XOOPS_URL . '/modules/' . $xoopsModule->getVar('dirname', 'n') . '/assets/css/styles.css', null);

// Get Permission to view abstract
$viewPermissionCat = XmnewsUtility::getPermissionCat('xmnews_viewabstract');
$keywords = '';
// Get start pager
$start = Request::getInt('start', 0);
$xoopsTpl->assign('start', $start);

$news_id = Request::getInt('news_id', 0);

$xoopsTpl->assign('index_module', $helper->getModule()->getVar('name'));
// Category
$news_cid = Request::getInt('news_cid', 0);
$xoopsTpl->assign('news_cid', $news_cid);
$criteria = new CriteriaCompo();
$criteria->add(new Criteria('category_status', 1));
if (!empty($viewPermissionCat)) {
	if (!in_array($news_cid , $viewPermissionCat) && $news_cid != 0){
		redirect_header('index.php?news_cid=0',2, _NOPERM);
	}
    $criteria->add(new Criteria('category_id', '(' . implode(',', $viewPermissionCat) . ')', 'IN'));
}
$criteria->setSort('category_weight ASC, category_name');
$criteria->setOrder('ASC');
$category_arr = $categoryHandler->getall($criteria);		
if (count($category_arr) > 0) {
	$news_cid_options = '<option value="0"' . ($news_cid == 0 ? ' selected="selected"' : '') . '>' . _ALL .'</option>';
	if (!empty($viewPermissionCat)) {
		foreach (array_keys($category_arr) as $i) {
			$news_cid_options .= '<option value="' . $i . '"' . ($news_cid == $i ? ' selected="selected"' : '') . '>' . $category_arr[$i]->getVar('category_name') . '</option>';
		}
	}
	$xoopsTpl->assign('news_cid_options', $news_cid_options);
}
// Criteria
$criteria = new CriteriaCompo();
$criteria->setSort('news_date');
$criteria->setOrder('DESC');
$criteria->setStart($start);
$criteria->setLimit($nb_limit);
$criteria->add(new Criteria('news_status', 1));
$criteria->add(new Criteria('news_date', time(),'<='));
if (!empty($viewPermissionCat)){
	$criteria->add(new Criteria('news_cid', '(' . implode(',', $viewPermissionCat) . ')', 'IN'));
}
if ($news_id != 0){
	$criteria->add(new Criteria('news_id', $news_id));
}
$description_SEO = '';
if ($news_cid != 0){
	// vérification si la categorie est activée
	$check_category = $categoryHandler->get($news_cid);
	if (empty($check_category)) {
		redirect_header('index.php', 2, _MA_XMNEWS_ERROR_NOCATEGORY);
	}
	if ($check_category->getVar('category_status') != 1){
		redirect_header('index.php', 2, _MA_XMNEWS_ERROR_NACTIVE);
	}	
	$criteria->add(new Criteria('news_cid', $news_cid));
	$xoopsTpl->assign('category_name', $category_arr[$news_cid]->getVar('category_name'));
	$category_img  = $category_arr[$news_cid]->getVar('category_logo');
	if ($category_img == ''){
		$xoopsTpl->assign('category_logo', '');
	} else {
		$xoopsTpl->assign('category_logo', $url_logo . $category_img);
	}
	$color = $category_arr[$news_cid]->getVar('category_color');
	if ($color == '#ffffff'){
		$xoopsTpl->assign('category_color', false);
		
	} else {
		$xoopsTpl->assign('category_color', $color);
	}
	$xoopsTpl->assign('category_description', $category_arr[$news_cid]->getVar('category_description'));
	$description_SEO = XmnewsUtility::generateDescriptionTagSafe($category_arr[$news_cid]->getVar('category_description'), 80);
	$xoopsTpl->assign('cat', true);
}else {
	$xoopsTpl->assign('cat', false);
}
$newsHandler->table_link = $newsHandler->db->prefix("xmnews_category");
$newsHandler->field_link = "category_id";
$newsHandler->field_object = "news_cid";
$news_arr = $newsHandler->getByLink($criteria);
$news_count = $newsHandler->getCount($criteria);
$xoopsTpl->assign('news_count', $news_count);
//xmsocial
if (xoops_isActiveModule('xmsocial') && $helper->getConfig('general_xmsocial', 0) == 1) {
	$xmsocial = true;
	xoops_load('utility', 'xmsocial');
} else {
    $xmsocial = false;
}
$xoopsTpl->assign('xmsocial', $xmsocial);
if ($news_count > 0 && !empty($viewPermissionCat)) {
	foreach (array_keys($news_arr) as $i) {
		$news_id                 = $news_arr[$i]->getVar('news_id');
		$news['id']              = $news_id;
		$news['cid']             = $news_arr[$i]->getVar('news_cid');
		$news['title']           = $news_arr[$i]->getVar('news_title');
		$news['author']          = XoopsUser::getUnameFromId($news_arr[$i]->getVar('news_userid'));
		$news['date']       	 = formatTimestamp($news_arr[$i]->getVar('news_date'), 's');
		if ($news_arr[$i]->getVar('news_mdate') != 0) {
			$news['mdate'] 		 = formatTimestamp($news_arr[$i]->getVar('news_mdate'), 's');
		}
		$news['description']     = $news_arr[$i]->getVar('news_description');
		$news['cat_name']        = $news_arr[$i]->getVar('category_name');
		$color					 = $news_arr[$i]->getVar('category_color');
		if ($color == '#ffffff'){
			$news['color']	 = false;
		} else {
			$news['color']	 = $color;
		}
		$news['counter']         = $news_arr[$i]->getVar('news_counter');
		if ($xmsocial == true){
			$news['rating'] = XmsocialUtility::renderVotes($news_arr[$i]->getVar('news_rating'), $news_arr[$i]->getVar('news_votes'));
		}
		$news['douser']          = $news_arr[$i]->getVar('news_douser');
		$news['dodate']          = $news_arr[$i]->getVar('news_dodate');
		$news['domdate']         = $news_arr[$i]->getVar('news_domdate');
		$news['dohits']          = $news_arr[$i]->getVar('news_dohits');
		$news['dorating']        = $news_arr[$i]->getVar('news_dorating');
		$news_img                = $news_arr[$i]->getVar('news_logo');
		$news['logo']        	 = $url_logo . $news_img;
		if ($news_img == ''){
			$news['logo']        = '';
		}
		if ($news_img == 'CAT'){
			$news['logo']        = $url_logo . $news_arr[$i]->getVar('category_logo');
		}
		$xoopsTpl->append_by_ref('news', $news);
		$keywords .= Metagen::generateSeoTitle($news['title']) . ',';
		unset($news);
	}
	// Display Page Navigation
	if ($news_count > $nb_limit) {
		$nav = new XoopsPageNav($news_count, $nb_limit, $start, 'start', 'news_cid=' . $news_cid);
		$xoopsTpl->assign('nav_menu', $nav->renderNav(4));
	}
} else {
	$xoopsTpl->assign('error_message', _MA_XMNEWS_ERROR_NONEWS);
}

//SEO
// pagetitle
$xoopsTpl->assign('xoops_pagetitle', strip_tags($xoopsModule->name()));
//description
if ($description_SEO == ''){
	$description_SEO =strip_tags($xoopsModule->name());
}
$xoTheme->addMeta('meta', 'description', $description_SEO);
//keywords
$keywords = substr($keywords, 0, -1);   
$xoTheme->addMeta('meta', 'keywords', $keywords);
include XOOPS_ROOT_PATH . '/footer.php';
