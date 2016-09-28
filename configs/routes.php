<?php
/**
 * Routes Configuration
 *
 * This files stores all the routes for the core WHSuite system.
 *
 * @package  WHSuite-Configs
 * @author  WHSuite Dev Team <info@whsuite.com>
 * @copyright  Copyright (c) 2013, Turn 24 Ltd.
 * @license http://whsuite.com/license/ The WHSuite License Agreement
 * @link http://whsuite.com
 * @since  Version 1.0
 */

App::get('router')->attach('', array(
    'name_prefix' => 'client-',
    'values' => array(
        'sub-folder' => 'client',
        'addon' => 'paypal'
    ),
    'params' => array(
        'id' => '(\d+)'
    ),

    'routes' => array(
        'paypal-invoice-return' => array(
            'params' => array(
                'invoice_id' => '(\d+)',
            ),
            'path' => '/paypal/pay-invoice/return/{:invoice_id}/',
            'values' => array(
                'controller' => 'PaypalController',
                'action' => 'payReturn'
            )
        ),
        'paypal-invoice-cancel' => array(
            'params' => array(
                'invoice_id' => '(\d+)',
            ),
            'path' => '/paypal/pay-invoice/cancel/{:invoice_id}/',
            'values' => array(
                'controller' => 'PaypalController',
                'action' => 'payCancel'
            )
        )
    )
));
