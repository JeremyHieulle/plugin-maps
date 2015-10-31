<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

use Galette\Entity\Adherent;
use GaletteMaps\NominatimTowns;
use GaletteMaps\Coordinates;

/**
 * Maps routes
 *
 * PHP version 5
 *
 * Copyright © 2015 The Galette Team
 *
 * This file is part of Galette (http://galette.tuxfamily.org).
 *
 * Galette is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Galette is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Galette. If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  Plugins
 * @package   GaletteMaps
 *
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2015 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @version   SVN: $Id$
 * @link      http://galette.tuxfamily.org
 * @since     0.9dev 2015-10-28
 */

//Constants and classes from plugin
require_once $module['root'] . '/_config.inc.php';

$app->get(
    '/',
    function () use ($app) {
        echo 'Coucou de Maps !';
    }
)->name('maps');

$app->get(
    '/localize-member/:id',
    $authenticate(),
    function ($id) use ($app, $module, $module_id) {
        $login = $app->login;
        $member = new Adherent((int)$id);

        if ($login->id != $id && !$login->isAdmin() && !$login->isStaff()) {
            //check if requested member is part of managed groups
            $groups = $member->groups;
            $is_managed = false;
            foreach ($groups as $g) {
                if ($login->isGroupManager($g->getId())) {
                    $is_managed = true;
                    break;
                }
            }
            if ($is_managed !== true) {
                //requested member is not part of managed groups, fall back to logged
                //in member
                $member->load($login->id);
                $id = $login->id;
            }
        }

        $coords = new Coordinates();
        $mcoords = $coords->getCoords($member->id);

        $towns = false;
        if (count($mcoords) === 0) {
            if ($member->town != '') {
                $t = new NominatimTowns();
                $towns = $t->search(
                    $member->town,
                    $member->country
                );
            }
        }

        $smarty = $app->view()->getInstance();
        $smarty->addTemplateDir(
            $module['root'] . '/templates/' . $app->preferences->pref_theme,
            $module['route']
        );
        $smarty->compile_id = MAPS_SMARTY_PREFIX;
        //set util paths
        $plugin_dir = basename(dirname($_SERVER['SCRIPT_NAME']));
        $smarty->assign(
            'galette_url',
            'http://' . $_SERVER['HTTP_HOST'] .
            preg_replace(
                "/\/plugins\/" . $plugin_dir . '/',
                "/",
                dirname($_SERVER['SCRIPT_NAME'])
            )
        );
        $smarty->assign(
            'plugin_url',
            'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/'
        );

        $params = [
            'page_title'        => _T("Maps") . ' - ' . str_replace(
                '%member',
                $member->sfullname,
                _T("%member geographic position")
            ),
            'member'            => $member,
            'require_dialog'    => true,
            'adh_map'           => true,
            'module_id'         => $module_id
        ];

        if ($towns !== false) {
            $params['towns'] = $towns;
        }

        if ($mcoords === false) {
            $app->flash(
                'error_detected',
                _T("Coordinates has not been loaded. Maybe plugin tables does not exists in the datatabase?")
            );
        } elseif (count($mcoords) > 0) {
            $params['town'] = $mcoords;
        }

        $app->render(
            'file:[' . $module['route'] . ']mymap.tpl',
            $params
        );
    }
)->name('maps_localize_member');

//member self localization
$app->get(
    '/mymap',
    $authenticate(),
    function () use ($app) {
        $deps = array(
            'picture'   => false,
            'groups'    => false,
            'dues'      => false
        );
        $member = new Adherent((int)$app->login->login, $deps);
        $app->redirect(
            $app->urlFor('maps_localize_member', [$member->id])
        );
    }
)->name('maps_mymap');

//global map page
$app->get(
    '/map',
    function () use ($app, $module, $module_id) {
        $login = $app->login;
        if (!$app->preferences->showPublicPages($login)) {
            //public pages are not actives
            $app->redirect(
                $app->urlFor('slash')
            );
        }

        $coords = new Coordinates();
        $list = $coords->listCoords();

        $smarty = $app->view()->getInstance();
        $smarty->addTemplateDir(
            $module['root'] . '/templates/' . $app->preferences->pref_theme,
            $module['route']
        );
        $smarty->compile_id = MAPS_SMARTY_PREFIX;

        //set util paths
        /*$plugin_dir = basename(dirname($_SERVER['SCRIPT_NAME']));
        $tpl->assign(
            'galette_url',
            'http://' . $_SERVER['HTTP_HOST'] .
            preg_replace(
                "/\/plugins\/" . $plugin_dir . '/',
                "/",
                dirname($_SERVER['SCRIPT_NAME'])
            )
        );
        $tpl->assign(
            'plugin_url',
            'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/'
        );*/

        $params = [
            'require_dialog'    => true,
            'page_title'        => _T("Maps"),
            'module_id'         => $module_id
        ];

        if (!$login->isLogged()) {
            $params['is_public'] = true;
        }

        if ($list !== false) {
            $params['list'] = $list;
        } else {
            $app->flash(
                'error_detected',
                _T("Coordinates has not been loaded. Maybe plugin tables does not exists in the datatabase?")
            );
        }

        $app->render(
            'file:[' . $module['route'] . ']maps.tpl',
            $params
        );
    }
)->name('maps_map');