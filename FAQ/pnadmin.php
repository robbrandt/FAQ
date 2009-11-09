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
 * the main administration function
 *
 * @author       The Zikula Development Team
 * @return       output       The main module admin page.
 */
function FAQ_admin_main()
{
    // Security check
    if (!SecurityUtil::checkPermission( 'FAQ::', '::', ACCESS_EDIT)) {
        return LogUtil::registerPermissionError();
    }

    // Create output object
    $pnRender = pnRender::getInstance('FAQ', false);

    // Return the output that has been generated by this function
    return $pnRender->fetch('faq_admin_main.htm');
}

/**
 * form to add new faq
 *
 * @author       The Zikula Development Team
 * @return       output       The main module admin page.
 */
function FAQ_admin_new()
{
    $dom = ZLanguage::getModuleDomain('FAQ');
    // Security check
    if (!SecurityUtil::checkPermission( 'FAQ::', '::', ACCESS_ADD)) {
        return LogUtil::registerPermissionError();
    }

    // Get the module vars
    $modvars = pnModGetVar('FAQ');

    // Create output object
    $pnRender = pnRender::getInstance('FAQ', false);

    if ($modvars['enablecategorization']) {
        // load the categories system
        if (!($class = Loader::loadClass('CategoryRegistryUtil'))) {
            pn_exit (__f('Error! Unable to load class [%s%]', 'CategoryRegistryUtil', $dom));
        }
        $catregistry = CategoryRegistryUtil::getRegisteredModuleCategories ('FAQ', 'faqanswer');

        $pnRender->assign('catregistry', $catregistry);
    }

    // Assign the module vars to the template
    $pnRender->assign($modvars);

    // Return the output that has been generated by this function
    return $pnRender->fetch('faq_admin_new.htm');
}

/**
 * Create an faq
 *
 * @author       The Zikula Development Team
 * @param        name         the name of the item to be created
 * @param        number       the number of the item to be created
 */
function FAQ_admin_create($args)
{
    $dom = ZLanguage::getModuleDomain('FAQ');
    // Get parameters from whatever input we need
    $faq = FormUtil::getPassedValue('faq', isset($args['faq']) ? $args['faq'] : null, 'POST');

    // Confirm authorisation code
    if (!SecurityUtil::confirmAuthKey()) {
        return LogUtil::registerAuthidError (pnModURL('FAQ', 'admin', 'view'));
    }

    // Create the FAQ
    $faqid = pnModAPIFunc('FAQ', 'admin', 'create', $faq);

    if ($faqid != false) {
        // Success
        LogUtil::registerStatus (__('Done! Item created.', $dom));
    }

    return pnRedirect(pnModURL('FAQ', 'admin', 'view'));
}

/**
 * modify an faq
 *
 * @author       The Zikula Development Team
 * @param        tid          the id of the item to be modified
 * @return       output       the modification page
 */
function FAQ_admin_modify($args)
{
    $dom = ZLanguage::getModuleDomain('FAQ');
    $faqid = FormUtil::getPassedValue('faqid', isset($args['faqid']) ? $args['faqid'] : null, 'GET');
    $objectid = FormUtil::getPassedValue('objectid', isset($args['objectid']) ? $args['objectid'] : null, 'GET');

    if (!empty($objectid)) {
        $faqid = $objectid;
    }

    $item = pnModAPIFunc('FAQ', 'user', 'get', array('faqid' => $faqid));
    if (!$item) {
        return LogUtil::registerError (__('No such item found.', $dom), 404);
    }

    // Security check
    if (!SecurityUtil::checkPermission( 'FAQ::', "$faqid::", ACCESS_EDIT)) {
        return LogUtil::registerPermissionError();
    }

    // Create output object
    $pnRender = pnRender::getInstance('FAQ', false);

    // Assign the item
    $pnRender->assign($item);

    // load the categories system
    if (!($class = Loader::loadClass('CategoryRegistryUtil'))) {
        pn_exit (__f('Error! Unable to load class [%s%]', 'CategoryRegistryUtil', $dom));
    }

    $categories = CategoryRegistryUtil::getRegisteredModuleCategories ('FAQ', 'faqanswer');
    $pnRender->assign('categories', $categories);

    $pnRender->assign(pnModGetVar('FAQ'));

    // Return the output that has been generated by this function
    return $pnRender->fetch('faq_admin_modify.htm');
}

/**
 * update the faq
 *
 * @author       The Zikula Development Team
 * @param        tid          the id of the item to be modified
 * @param        name         the name of the item to be updated
 * @param        number       the number of the item to be updated
 */
function FAQ_admin_update($args)
{
    $dom = ZLanguage::getModuleDomain('FAQ');
    $faq = FormUtil::getPassedValue('faq', isset($args['faq']) ? $args['faq'] : null, 'POST');
    if (!empty($faq['objectid'])) {
        $faq['faqid'] = $faq['objectid'];
    }

    // Confirm authorisation code
    if (!SecurityUtil::confirmAuthKey()) {
        return LogUtil::registerAuthidError (pnModURL('FAQ', 'admin', 'view'));
    }

    // Update FAQ
    if (pnModAPIFunc('FAQ', 'admin', 'update', $faq)) {
        // Success
        LogUtil::registerStatus (__('Done! Item updated.', $dom));
    }

    return pnRedirect(pnModURL('FAQ', 'admin', 'view'));
}

/**
 * delete an faq
 *
 * @author       The Zikula Development Team
 * @param        tid            the id of the item to be modified
 * @param        confirmation   confirmation that this item can be deleted
 */
function FAQ_admin_delete($args)
{
    $dom = ZLanguage::getModuleDomain('FAQ');
    $faqid = FormUtil::getPassedValue('faqid', isset($args['faqid']) ? $args['faqid'] : null, 'REQUEST');
    $objectid = FormUtil::getPassedValue('objectid', isset($args['objectid']) ? $args['objectid'] : null, 'REQUEST');
    $confirmation = FormUtil::getPassedValue('confirmation', null, 'POST');
    if (!empty($objectid)) {
        $faqid = $objectid;
    }

    // Get the current FAQ
    $item = pnModAPIFunc('FAQ', 'user', 'get', array('faqid' => $faqid));

    if (!$item) {
        return LogUtil::registerError (__('No such item found.', $dom), 404);
    }

    // Security check
    if (!SecurityUtil::checkPermission( 'FAQ::', "$faqid::", ACCESS_DELETE)) {
        return LogUtil::registerPermissionError();
    }

    // Check for confirmation.
    if (empty($confirmation)) {
        // No confirmation yet

        // Create output object
        $pnRender = pnRender::getInstance('FAQ', false);

        // Add a hidden field for the item ID to the output
        $pnRender->assign('faqid', $faqid);

        // Return the output that has been generated by this function
        return $pnRender->fetch('faq_admin_delete.htm');
    }

    // If we get here it means that the user has confirmed the action

    // Confirm authorisation code
    if (!SecurityUtil::confirmAuthKey()) {
        return LogUtil::registerAuthidError (pnModURL('FAQ', 'admin', 'view'));
    }

    // delete the faq
    if (pnModAPIFunc('FAQ', 'admin', 'delete', array('faqid' => $faqid))) {
        // Success
        LogUtil::registerStatus (__('Done! Item deleted.', $dom));
    }

    return pnRedirect(pnModURL('FAQ', 'admin', 'view'));
}

/**
 * view items
 *
 * This function shows all items and lists the administration
 * options.
 *
 * @author       The Zikula Development Team
 * @param        startnum     The number of the first item to show
 * @return       output       The main module admin page
 */
function FAQ_admin_view($args)
{
    $dom = ZLanguage::getModuleDomain('FAQ');
    // Security check
    if (!SecurityUtil::checkPermission( 'FAQ::', '::', ACCESS_EDIT)) {
        return LogUtil::registerPermissionError();
    }

    $startnum = FormUtil::getPassedValue('startnum', isset($args['startnum']) ? $args['startnum'] : null, 'GET');
    $property = FormUtil::getPassedValue('faq_property', isset($args['faq_property']) ? $args['faq_property'] : null, 'POST');
    $category = FormUtil::getPassedValue("faq_{$property}_category", isset($args["faq_{$property}_category"]) ? $args["faq_{$property}_category"] : null, 'POST');
    $clear    = FormUtil::getPassedValue('clear', false, 'POST');
    $purge    = FormUtil::getPassedValue('purge', false, 'GET');

    if ($purge) {
        if (pnModAPIFunc('FAQ', 'admin', 'purgepermalinks')) {
            LogUtil::registerStatus(__('Purging of the pemalinks was successful', $dom));
        } else {
            LogUtil::registerError(__('Purging of the pemalinks has failed', $dom));
        }
        return pnRedirect(strpos(pnServerGetVar('HTTP_REFERER'), 'purge') ? pnModURL('FAQ', 'admin', 'view') : pnServerGetVar('HTTP_REFERER'));
    }
    if ($clear) {
        $property = null;
        $category = null;
    }

    // get module vars for later use
    $modvars = pnModGetVar('FAQ');

    if ($modvars['enablecategorization']) {
        // load the category registry util
        if (!($class = Loader::loadClass('CategoryRegistryUtil'))) {
            pn_exit (__f('Error! Unable to load class [%s%]', 'CategoryRegistryUtil', $dom));
        }
        $catregistry  = CategoryRegistryUtil::getRegisteredModuleCategories('FAQ', 'faqanswer');
        $properties = array_keys($catregistry);

        // Validate and build the category filter - mateo
        if (!empty($property) && in_array($property, $properties) && !empty($category)) {
            $catFilter = array($property => $category);
        }

        // Assign a default property - mateo
        if (empty($property) || !in_array($property, $properties)) {
            $property = $properties[0];
        }

        // plan ahead for ML features
        $propArray = array();
        foreach ($properties as $prop) {
            $propArray[$prop] = $prop;
        }
    }

    // get all faq's
    $items = pnModAPIFunc('FAQ', 'user', 'getall',
                          array('startnum' => $startnum,
                                'numitems' => $modvars['itemsperpage'],
                                'category' => isset($catFilter) ? $catFilter : null,
                                'catregistry'  => isset($catregistry) ? $catregistry : null));

    foreach ($items as $key => $item) {
        $options = array();
        if (SecurityUtil::checkPermission( 'FAQ::', "$item[faqid]::", ACCESS_EDIT)) {
            $options[] = array('url'   => pnModURL('FAQ', 'admin', 'modify', array('faqid' => $item['faqid'])),
                               'image' => 'xedit.gif',
                               'title' => __('Edit', $dom));
            if (SecurityUtil::checkPermission( 'FAQ::', "$item[faqid]::", ACCESS_DELETE)) {
                $options[] = array('url'   => pnModURL('FAQ', 'admin', 'delete', array('faqid' => $item['faqid'])),
                                   'image' => '14_layer_deletelayer.gif',
                                   'title' => __('Delete', $dom));
            }
        }

        // Add the calculated menu options to the item array
        $items[$key]['options'] = $options;
    }

    // Create output object
    $pnRender = pnRender::getInstance('FAQ', false);

    // Assign the items and modvars to the template
    $pnRender->assign('faqs', $items);
    $pnRender->assign($modvars);

    // Assign the default language
    $pnRender->assign('lang', pnUserGetLang());

    // Assign the categories information if enabled
    if ($modvars['enablecategorization']) {
        $pnRender->assign('catregistry', $catregistry);
        $pnRender->assign('numproperties', count($propArray));
        $pnRender->assign('properties', $propArray);
        $pnRender->assign('property', $property);
        $pnRender->assign("category", $category);
    }

    // assign the values for the smarty plugin to produce a pager
    $pnRender->assign('pager', array('numitems' => pnModAPIFunc('FAQ', 'user', 'countitems', array('category' => isset($catFilter) ? $catFilter : null)),
                                     'itemsperpage' => $modvars['itemsperpage']));

    // Return the output that has been generated by this function
    return $pnRender->fetch('faq_admin_view.htm');
}

/**
 * Modify configuration
 *
 * This is a standard function to modify the configuration parameters of the
 * module
 *
 * @author       The Zikula Development Team
 * @return       output       The configuration page
 */
function FAQ_admin_modifyconfig()
{
    // Security check
    if (!SecurityUtil::checkPermission( 'FAQ::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // Create output object
    $pnRender = pnRender::getInstance('FAQ', false);

    // Assign all module vars
    $pnRender->assign(pnModGetVar('FAQ'));

    // Return the output that has been generated by this function
    return $pnRender->fetch('faq_admin_modifyconfig.htm');
}

/**
 * Update the configuration
 *
 * This is a standard function to update the configuration parameters of the
 * module given the information passed back by the modification form
 * Modify configuration
 *
 * @author       The Zikula Development Team
 * @param        itemsperpage   number of items per page
 */
function FAQ_admin_updateconfig()
{
    $dom = ZLanguage::getModuleDomain('FAQ');
    // Security check
    if (!SecurityUtil::checkPermission( 'FAQ::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // Confirm authorisation code
    if (!SecurityUtil::confirmAuthKey()) {
        return LogUtil::registerAuthidError (pnModURL('FAQ', 'admin', 'view'));
    }

    // Update module variables
    $itemsperpage = FormUtil::getPassedValue('itemsperpage', 25, 'POST');
    pnModSetVar('FAQ', 'itemsperpage', $itemsperpage);
    $enablecategorization = (bool)FormUtil::getPassedValue('enablecategorization', false, 'POST');
    pnModSetVar('FAQ', 'enablecategorization', $enablecategorization);
    $addcategorytitletopermalink = (bool)FormUtil::getPassedValue('addcategorytitletopermalink', false, 'POST');
    pnModSetVar('FAQ', 'addcategorytitletopermalink', $addcategorytitletopermalink);

    // The configuration has been changed, so we clear all caches for this module.
    $pnRender = pnRender::getInstance('FAQ');
    $pnRender->clear_all_cache();

   // Let any other modules know that the modules configuration has been updated
    pnModCallHooks('module','updateconfig','FAQ', array('module' => 'FAQ'));

    // the module configuration has been updated successfuly
    LogUtil::registerStatus (__('Done! Module configuration updated.', $dom));

    return pnRedirect(pnModURL('FAQ', 'admin', 'view'));
}
