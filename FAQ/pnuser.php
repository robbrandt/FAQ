<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c) 2002, Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Zikula_Value_Addons
 * @subpackage FAQ
 */

/**
 * the main user function
 *
 * This function is the default function, and is called whenever the module is
 * initiated without defining arguments.  As such it can be used for a number
 * of things, but most commonly it either just shows the module menu and
 * returns or calls whatever the module designer feels should be the default
 * function (often this is the view() function)
 *
 * @author       The Zikula Development Team
 * @return       output       The main module page
 */
function FAQ_user_main()
{
    $dom = ZLanguage::getModuleDomain('FAQ');
    // Security check
    if (!SecurityUtil::checkPermission( 'FAQ::', '::', ACCESS_OVERVIEW)) {
        return LogUtil::registerPermissionError();
    }

    // Create output object
    $render = & Renderer::getInstance('FAQ');

    // load the categories system
    if (ModUtil::getVar('FAQ', 'enablecategorization')) {
        if (!Loader::loadClass('CategoryUtil') || !Loader::loadClass('CategoryRegistryUtil')) {
            z_exit(__f('Error! Unable to load class [%s%]', 'CategoryUtil | CategoryRegistryUtil', $dom));
        }
        $catregistry = CategoryRegistryUtil::getRegisteredModuleCategories('FAQ', 'faqanswer');
        $categories = array();
        $ak = array_keys($catregistry);
        foreach ($ak as $k) {
            $categories[$k] = CategoryUtil::getCategoryByID($catregistry[$k]);
            $categories[$k]['path'] .= '/';
            $categories[$k]['subcategories'] = CategoryUtil::getCategoriesByParentID($catregistry[$k]);
        }
        $render->assign('categories', $categories);
    }

    $render->assign('lang', ZLanguage::getLanguageCode());
    $render->assign(ModUtil::getVar('FAQ'));
    $render->assign('shorturls', System::getVar('shorturls'));
    $render->assign('shorturlstype', System::getVar('shorturlstype'));

    // Return the output that has been generated by this function
    return $render->fetch('faq_user_main.htm');
}

/**
 * view items
 *
 * This is a standard function to provide an overview of all of the items
 * available from the module.
 *
 * @author       The Zikula Development Team
 * @param        integer      $startnum    (optional) The number of the start item
 * @return       output       The overview page
 */
function FAQ_user_view($args)
{
    // Security check
    if (!SecurityUtil::checkPermission( 'FAQ::', '::', ACCESS_OVERVIEW)) {
        return LogUtil::registerPermissionError();
    }

    $dom = ZLanguage::getModuleDomain('FAQ');

    $startnum = isset($args['startnum']) ? $args['startnum'] : (int)FormUtil::getPassedValue('startnum', 1, 'GET');
    $cat      = isset($args['cat']) ? $args['cat'] : (string)FormUtil::getPassedValue('cat', null, 'GET');
    $prop     = isset($args['prop']) ? $args['prop'] : (string)FormUtil::getPassedValue('prop', null, 'GET');
    $func     = (string)FormUtil::getPassedValue('func');

    // defaults and input validation
    if (!is_numeric($startnum) || $startnum < 0) {
        $startnum = 1;
    }

    // get all module vars for later use
    $modvars = ModUtil::getVar('FAQ');

    // check if categorisation is enabled
    // and if its requested to list the recent faqs
    if ($modvars['enablecategorization'] && !empty($prop) && !empty($cat)) {
        if (!Loader::loadClass('CategoryUtil') || !Loader::loadClass('CategoryRegistryUtil')) {
            z_exit(__f('Error! Unable to load class [%s%]', 'CategoryUtil | CategoryRegistryUtil', $dom));
        }
        // get the categories registered for the Pages
        $catregistry = CategoryRegistryUtil::getRegisteredModuleCategories('FAQ', 'faqanswer');
        $properties  = array_keys($catregistry);

        // if the property and the category are specified
        // means that we'll list the FAQs that belongs to that category
        if (in_array($prop, $properties)) {
            if (!is_numeric($cat)) {
                $rootCat = CategoryUtil::getCategoryByID($catregistry[$prop]);
                $cat = CategoryUtil::getCategoryByPath($rootCat['path'].'/'.$cat);
            } else {
                $cat = CategoryUtil::getCategoryByID($cat);
            }
            if (!empty($cat) && isset($cat['path'])) {
                // include all it's subcategories and build the filter
                $categories = categoryUtil::getCategoriesByPath($cat['path'], '', 'path');
                $catstofilter = array();
                foreach ($categories as $category) {
                    $catstofilter[] = $category['id'];
                }
                $catFilter = array($prop => $catstofilter);
            } else {
            	LogUtil::registerError(__('Invalid category passed.', $dom));
            }
        }
    }

    // get all faqs
    $items = ModUtil::apiFunc('FAQ', 'user', 'getall',
                          array('startnum' => $startnum,
                                'numitems' => $modvars['itemsperpage'],
                                'answered' => true,
                                'category' => isset($catFilter) ? $catFilter : null,
                                'catregistry' => isset($catregistry) ? $catregistry : null));

    // Create output object
    $render = & Renderer::getInstance('FAQ', false);

    // assign various useful template variables
    $render->assign('startnum', $startnum);
    $render->assign('category', $cat);
    $render->assign('property', $prop);
    $render->assign('lang', ZLanguage::getLanguageCode());
    $render->assign($modvars);
    $render->assign('shorturls', System::getVar('shorturls'));
    $render->assign('shorturlstype', System::getVar('shorturlstype'));

    // Loop through each item getting the rendered output from the item template
    $faqitems = array();
    $faqs = array();
    foreach ($items as $item) {
        if (SecurityUtil::checkPermission( 'FAQ::', "$item[faqid]::", ACCESS_OVERVIEW)) {
            $render->assign($item);
            $faqitems[] = $render->fetch('faq_user_row_read.htm', $item['faqid']);
            $faqs[] = $item;
        }
    }

    // Display the entries
    $render->assign('items', $faqitems);
    $render->assign('faqs', $faqs);
    $render->assign('func', $func);

    // assign the start number
    $render->assign('startnum', $startnum);

    // assign the values for the smarty plugin to produce a pager
    $render->assign('pager', array('numitems'     => ModUtil::apiFunc('FAQ', 'user', 'countitems', array('category' => isset($catFilter) ? $catFilter : null)),
                                     'itemsperpage' => ModUtil::getVar('FAQ', 'itemsperpage')));

    // Return the output that has been generated by this function
    return $render->fetch('faq_user_view.htm');
}

/**
 * display item
 *
 * This is a standard function to provide detailed informtion on a single item
 * available from the module.
 *
 * @author       The Zikula Development Team
 * @param        integer      $tid     the ID of the item to display
 * @return       output       The item detail page
 */
function FAQ_user_display($args)
{
    $dom = ZLanguage::getModuleDomain('FAQ');

    $faqid    = FormUtil::getPassedValue('faqid', isset($args['faqid']) ? $args['faqid'] : null, 'REQUEST');
    $title    = FormUtil::getPassedValue('title', isset($args['title']) ? $args['title'] : null, 'REQUEST');
    $objectid = FormUtil::getPassedValue('objectid', isset($args['objectid']) ? $args['objectid'] : null, 'REQUEST');
    if (!empty($objectid)) {
        $faqid = $objectid;
    }

    // Validate the essential parameters
    if ((empty($faqid) || !is_numeric($faqid)) && (empty($title))) {
        return LogUtil::registerArgsError();
    }
    if (!empty($title)) {
        unset($faqid);
    }

    // Create output object
    $render = & Renderer::getInstance('FAQ');

    // set the cache id
    if (isset($faqid)) {
        $render->cache_id = $faqid;
    } else {
        $render->cache_id = $title;
    }

    // check out if the contents are cached.
    if ($render->is_cached('faq_user_display.htm')) {
       return $render->fetch('FAQ_user_display.htm');
    }

    // Get the faq
    if (isset($faqid)) {
        $item = ModUtil::apiFunc('FAQ', 'user', 'get', array('faqid' => $faqid));
    } else {
        $item = ModUtil::apiFunc('FAQ', 'user', 'get', array('title' => $title));
        System::queryStringSetVar('faqid', $item['faqid']);
    }

    if ($item === false) {
        return LogUtil::registerError(__('Failed to get any items', $dom), 404);
    }

    // set the page title
    PageUtil::setVar('title', $item['question']);

    // Assign details of the item.
    $render->assign($item);

    // Return the output that has been generated by this function
    return $render->fetch('faq_user_display.htm');
}

/**
 * ask a question
 *
 * @author       The Zikula Development Team
 * @return       output       A form to submit a question
 */
function FAQ_user_ask()
{
    // Security check
    if (!SecurityUtil::checkPermission( 'FAQ::', '::', ACCESS_COMMENT)) {
        return LogUtil::registerPermissionError();
    }

    // Create output object
    $render = & Renderer::getInstance('FAQ');

    // assign logged in state
    $render->assign('loggedin', UserUtil::isLoggedIn());

    // Return the output that has been generated by this function
    return $render->fetch('faq_user_ask.htm');

}

/**
 * Create an faq
 *
 * @author       The Zikula Development Team
 * @param        question     the question to be submitted
 */
function FAQ_user_create($args)
{
    // Confirm authorisation code
    if (!SecurityUtil::confirmAuthKey()) {
        return LogUtil::registerAuthidError(ModUtil::url('FAQ', 'user', 'view'));
    }

    $dom = ZLanguage::getModuleDomain('FAQ');

    // Get parameters from whatever input we need
    $faq = FormUtil::getPassedValue('faq', isset($args['faq']) ? $args['faq'] : null, 'POST');

    if (UserUtil::isLoggedIn() || !isset($faq['submittedby'])) {
        $faq['submittedby'] = '';
    }

    // Create the FAQ
    $faqid = ModUtil::apiFunc('FAQ', 'admin', 'create',
                          array('question' => $faq['question'],
                                'answer' => '',
                                'submittedby' => $faq['submittedby']));

    if ($faqid != false) {
        // Success
        LogUtil::registerStatus(__('Thank you for your question', $dom));
    }

    return System::redirect(ModUtil::url('FAQ', 'user', 'view'));
}
