<?php
/* (C) Websplosion LTD., 2001-2014

IMPORTANT: This is a commercial software product
and any kind of using it must agree to the Websplosion's license agreement.
It can be found at http://www.chameleonsocial.com/license.doc

This notice may not be removed from the source code. */

include("./_include/core/main_start.php");


$optionTmplName = Common::getTmplName();
$show = get_param('show');
$isCustomShowList = in_array($show, array('wall_liked', 'wall_shared', 'wall_liked_comment',
                                          'photo_liked', 'photo_liked_comment',
                                          'video_liked', 'video_liked_comment',
                                          'blogs_post_liked', 'blogs_post_liked_comment'));

if ($optionTmplName == 'edge' && $isCustomShowList) {
    if (isset($l[$p]["page_title_{$show}"])) {//EDGE
        $l[$p]['page_title'] = $l[$p]["page_title_{$show}"];
    }
    Common::$pagerUrl = Common::pageUrl($show);
}

$addWhereLocation = true;

$display = get_param('display');

if (!guid() && $optionTmplName == 'edge') {
    if (!$display && Common::isOptionActive('list_people_hide_from_guests', "{$optionTmplName}_general_settings")) {
        Common::toLoginPage();
    } elseif ($display == 'profile') {
        Common::toLoginPage();
    }
}

if (!get_param('join_search_page')){
    Common::checkAreaLogin();
}


if (in_array($display, array('encounters','rate_people'))) {
    if (guid()) {
        if ($display == 'encounters') {
            $isShow = true;
            if ($optionTmplName == 'impact') {
                $isShow = CustomPage::checkItemShow('column_narrow_hot_or_not');
            } elseif ($optionTmplName == 'urban') {
                $submenuItem = Menu::getIndexItemSubmenu('header_menu', 'header_menu_encounters_item');
                $isShow = $submenuItem !== false;
            }
            if (!$isShow) {
                Common::toHomePage();
            }
        } elseif ($display == 'rate_people' && !Common::isOptionActive('photo_rating_enabled')) {
            Common::toHomePage();
        }
    } else {
        Common::toLoginPage();
    }
}

CustomPage::setSelectedMenuItemInSearchResults($display);

$params = get_params_string();

$userSearchFiltersUpdate = false;
$optionSet = Common::getOption('set', 'template_options');
$isFreeSite = Common::isOptionActive('free_site');
if (guid()){
    /* Redirect blocked user */
    User::accessCheckToProfile();
    /* Redirect blocked user */

    /* Encounters */
    $isAjaxRequest = get_param('ajax');
    if ($display == 'encounters' && $isAjaxRequest) {
        Encounters::undoLike();
    }
    /* Encounters */
    $name = trim(get_param('search_name', ''));
    if ($name == '') $name = l('my_search');
	if (get_param('save_search', 0) == 1)
	{
		$name = to_sql($name, 'Text');
		$id = DB::result("SELECT id FROM search_save WHERE user_id=" . to_sql(guid(), 'Number') . " AND name=" . $name . "");

		if ($id == 0)
		{
			$num_save = DB::result("SELECT COUNT(id) FROM search_save WHERE user_id=" . to_sql(guid(), 'Number') . "");
    		$query = to_sql($params, "Text");
			DB::execute("INSERT INTO search_save (name, user_id, query) VALUES (" . $name . ", " . to_sql(guid(), 'Number') . ", " . $query . ")");
		}
		else
		{
			$query = to_sql($params, "Text");
			DB::execute("UPDATE search_save SET query=" . $query . " WHERE id=" . $id . "");
		}
	}

    /*if(Common::getOption('set', 'template_options') == 'urban' && ($display == '' || $display == 'encounters' || $display == 'rate_people')) {
        if(!get_param('set_filter')) {
            global $g_user;
            $userinfo = User::getInfoFull($g_user['user_id']);
            $g_user['user_search_filters'] = $userinfo['user_search_filters'];
            $g_user['state_filter_search'] = $userinfo['state_filter_search'];
            $filters = $userinfo['user_search_filters'];
            if($filters) {
                $filtersInfo = json_decode($filters, true);
                //var_dump($filtersInfo);
                foreach($filtersInfo as $filterInfoKey => $filterInfoValue) {
                    if(isset($filterInfoValue['values'])) {
                        foreach($filterInfoValue['values'] as $key => $value) {
                            $_GET[$key] = $value;
                        }
                    } else {
                        $_GET[$filterInfoKey] = $filterInfoValue['value'];
                    }
                }
            }

            $_GET['country'] = get_param('country', $g_user['country_id']);
            $_GET['state'] = get_param('state', $g_user['state_id']);
            $_GET['city'] = get_param('city', $g_user['city_id']);

            if (UserFields::isActive('age_range')) {
                $_GET['p_age_from'] = get_param('p_age_from', $g_user['p_age_from']);
                $_GET['p_age_to'] = get_param('p_age_to', $g_user['p_age_to']);
            }
            if (UserFields::isActive('i_am_here_to')) {
                $_GET['i_am_here_to'] = get_param('i_am_here_to', $g_user['i_am_here_to']);
            }
            if(UserFields::isActive('orientation')) {
                $_GET['p_orientation'] = UserFields::checksToParamsArray('const_orientation', $g_user['p_orientation']);
            }

        } else {
            // save some fields to profile
            // i_am_here_to
            // p_orientation
            // p_age_from
            // p_age_to
            // location
            // distance
            $data = array();
            $fields = array(
                'i_am_here_to',
                'p_orientation',
                'p_age_from',
                'p_age_to',
            );
            $fieldsActive = array(
                'i_am_here_to' => 'i_am_here_to',
                'p_orientation' => 'orientation',
                'p_age_from' => 'age_range',
                'p_age_to' => 'age_range',
            );
            foreach($fields as $field) {
                if (!UserFields::isActive($fieldsActive[$field])) {
                    continue;
                }
                $fieldValue = get_param($field);
                if($field == 'p_orientation') {
                    $fieldValue = get_checks_param($field);
                }
                if(guser($field) != $fieldValue) {
                    $data[$field] = $fieldValue;
                }
            }

            if($data) {
                //var_dump($data);
                User::update($data);
            }
        }
        set_session('search_offset', get_param('offset', 1));
        $userSearchFiltersUpdate = true;
    }*/
}
if ($optionSet == 'urban' && ($display == '' || $display == 'encounters' || $display == 'rate_people')) {
    if (guid()) {
        global $g_user;
        $userinfo = User::getInfoFull($g_user['user_id'], 0, true);
        /*$g_user['user_search_filters'] = $userinfo['user_search_filters'];
        $g_user['user_search_filters_mobile'] = $userinfo['user_search_filters_mobile'];//???*/
        $g_user['user_search_filters_mobile'] = User::getParamsFilter('user_search_filters_mobile', $userinfo['user_search_filters_mobile']);
        $g_user['state_filter_search'] = $userinfo['state_filter_search'];
        if(!get_param('set_filter') || get_param('set_filter_interest')) {
            User::setGetParamsFilter('user_search_filters', $userinfo);
            /*$filters = $userinfo['user_search_filters'];
            if($filters) {
                $filtersInfo = json_decode($filters, true);
                //var_dump($filtersInfo);
                foreach($filtersInfo as $filterInfoKey => $filterInfoValue) {
                    if(isset($filterInfoValue['values'])) {
                        foreach($filterInfoValue['values'] as $key => $value) {
                            $_GET[$key] = $value;
                        }
                    } else {
                        $_GET[$filterInfoKey] = $filterInfoValue['value'];

//                        if (is_array($filterInfoValue['value'])) {
//                            foreach ($filterInfoValue['value'] as $key => $item) {
//                                foreach ($item as $k => $value) {
//                                    $_GET[$filterInfoKey][] = $value;
//                                }
//                            }
//                        } else {
//                            $_GET[$filterInfoKey] = $filterInfoValue['value'];
//                        }
                    }
                }
            }

            $_GET['country'] = get_param('country', $g_user['country_id']);
            $_GET['state'] = get_param('state', $g_user['state_id']);
            $_GET['city'] = get_param('city', $g_user['city_id']);

            if (UserFields::isActive('age_range')) {
                $_GET['p_age_from'] = get_param('p_age_from', $g_user['p_age_from']);
                $_GET['p_age_to'] = get_param('p_age_to', $g_user['p_age_to']);
            }
            if (UserFields::isActive('i_am_here_to')) {
                $_GET['i_am_here_to'] = get_param('i_am_here_to', $g_user['i_am_here_to']);
            }
            if(UserFields::isActive('orientation')) {
                $_GET['p_orientation'] = UserFields::checksToParamsArray('const_orientation', $g_user['p_orientation']);
            }*/
            $_GET['with_photo'] = get_param('with_photo', 1);
            if (get_param('set_filter_interest')) {
                $userSearchFiltersUpdate = true;
            }
        } else {
            $g_user['user_search_filters'] = User::getParamsFilter('user_search_filters', $userinfo['user_search_filters']);
            User::updateParamsFilterUser();
            // save some fields to profile
            // i_am_here_to
            // p_orientation
            // p_age_from
            // p_age_to
            // location
            // distance
            /*$data = array();
            $fields = array(
                'i_am_here_to',
                'p_orientation',
                'p_age_from',
                'p_age_to',
            );
            $fieldsActive = array(
                'i_am_here_to' => 'i_am_here_to',
                'p_orientation' => 'orientation',
                'p_age_from' => 'age_range',
                'p_age_to' => 'age_range',
            );
            foreach($fields as $field) {
                if (!UserFields::isActive($fieldsActive[$field])) {
                    continue;
                }
                $fieldValue = get_param($field);
                if($field == 'p_orientation') {
                    $fieldValue = get_checks_param($field);
                }
                if(guser($field) != $fieldValue) {
                    $data[$field] = $fieldValue;
                }
            }

            if($data) {
                //var_dump($data);
                User::update($data);
            }*/
        }
        $userSearchFiltersUpdate = true;
    } else {
        $isAjaxRequest = get_param('ajax');
        $setDefaultIamHereTo = false;
        $paramIamHereTo = get_param('i_am_here_to');
        if (!get_param('set_filter')) {
            $setDefaultIamHereTo = true;
            $userinfo['state_filter_search'] = 1;
            $userinfo['user_search_filters'] = User::setDefaultParamsFilterUser(0, false);
            User::setGetParamsFilter('user_search_filters', $userinfo);
        } elseif (!$paramIamHereTo && !$isAjaxRequest) {
            $setDefaultIamHereTo = true;
        }
        if ($setDefaultIamHereTo && UserFields::isActive('i_am_here_to') && $optionTmplName != 'edge') {
            $sql = 'SELECT `id` FROM `const_i_am_here_to`';
            $iAmHereTo = DB::result($sql);
            if ($iAmHereTo) {
                $_GET['i_am_here_to'] = $iAmHereTo;
            }
        }
            /*$geoInfo = getDemoCapitalCountry();//IP::geoInfoCity();
            $_GET['country'] = get_param('country', $geoInfo['country_id']);
            $_GET['state'] = get_param('state', $geoInfo['state_id']);
            $_GET['city'] = get_param('city', $geoInfo['city_id']);
            $_GET['with_photo'] = get_param('with_photo', 1);
            //UsersFilter::setSettingsNotLogged();
        }/* else {
            $userSearchFiltersUpdate = true;
            set_session('search_offset', get_param('offset', 1));
        }*/
    }
}

//var_dump($_GET);

$where = "";
$whereCore = "1=1 ";
$userSearchFilters = array();

$user['i_am_here_to'] = (int) get_param('i_am_here_to', '');
if ($user['i_am_here_to'])
{
    //$where .= " AND u.i_am_here_to = " . to_sql($user['i_am_here_to']);
}

$user["horoscope"] = (int) get_checks_param("p_star_sign");
if ($user["horoscope"])
{
	$where .= " AND " . $user["horoscope"] . " & (1 << (cast(u.horoscope AS signed) - 1))";
}

$pOrientationSearch = intval(get_param('p_orientation_search'));
if ($pOrientationSearch) {
    $user["p_orientation"] = $pOrientationSearch;
} else {
    $user["p_orientation"] = (int) get_checks_param("p_orientation");
}

if ($user["p_orientation"] > 0)
{
	$where .= " AND " . $user["p_orientation"] . " & (1 << (cast(u.orientation AS signed) - 1))";
}
$user["p_relation"] = (int) get_checks_param("p_relation");
if ($user["p_relation"] != "0")
{
	$where .= " AND " . $user["p_relation"] . " & (1 << (cast(u.relation AS signed) - 1))";
}

$user['name'] = get_param("name_key", "");
if ($user['name'] != '')
{
    $where .= " AND u.name LIKE '%" . to_sql($user['name'], "Plain") . "%'";
}

$user['name'] = get_param("name", "");
if ($user['name'] != "")
{
	$where .= " AND u.name=" . to_sql($user['name'], 'Text') . "";
}

$user['name_seo'] = get_param('name_seo');
if ($user['name_seo'] != '')
{
	$where .= ' AND u.name_seo = ' . to_sql($user['name_seo'], 'Text');
}

$user['p_age_from'] = (int) get_param("p_age_from", 0);
$user['p_age_to'] = (int) get_param("p_age_to", 0);
$userAgeToSrc = $user['p_age_to'];
if ($user['p_age_from'] == $g['options']['users_age']) $user['p_age_from'] = 0;
if ($user['p_age_to'] == $g['options']['users_age_max']) $user['p_age_to'] = 10000;
if ($user['p_age_from'] != 0)
{
	$where .= " AND u.birth <= " . to_sql(Common::ageToDate($user['p_age_from']));
}

if ($userAgeToSrc && $userAgeToSrc != $g['options']['users_age_max'])
{
    $where .= " AND u.birth >= " . to_sql(Common::ageToDate($userAgeToSrc, true));
}

foreach ($g['user_var'] as $k => $v){
	$user[$k] = intval(get_param($k, ''));
}
$typeFields = array('from', 'checks', 'checkbox');
$numCheckbox = 0;
$from_add = '';
$from_group = '';
foreach ($g['user_var'] as $k => $v)
{
    if (in_array($v['type'], $typeFields) && $v['status'] == 'active')
    {
        if ($v['type'] == 'from'){

            $key = $k;
			if (substr($key, 0, 2) == "p_") $key = substr($key, 2);
			if (substr($key, -5) == "_from") $key = substr($key, 0, strlen($key) - 5);

            $valFieldFrom = $user[$k];

            $fieldTo = substr($k, 0, strlen($k) - 4) . 'to';
            $valFieldTo = intval($user[$fieldTo]);

//            if($user[$k]) {
//                $first = DB::result("SELECT id FROM " . $v[2] . " ORDER BY id ASC LIMIT 1");
//                if ($first == $user[$k]){
//                    //$user[$k] = 0;
//                }
//            } else {
//                $valFieldFrom = '';
//            }

            if ($valFieldTo) {
                $where .= ' AND i.' . $key . '<=' . $valFieldTo;

                if(!$valFieldFrom) {
                    $valFieldFrom = 1;
                }
            }

            if($valFieldFrom) {
                $where .= " AND i." . $key . ">=" . intval($valFieldFrom);
            }

            if($valFieldFrom || $valFieldTo) {
                // save real value for defaul select value
                $valFieldFrom = $user[$k];

                $keyFilter = $k;

                if(!$valFieldFrom) {
                    $keyFilter = $fieldTo;
                }

                $userSearchFilters[$keyFilter] = array(
                    'field' => $key,
                    'values' => array($k => $valFieldFrom, $fieldTo => $valFieldTo),
                );

                //$userSearchFilters[$k] = array($user[$k], $valFieldsTo);
            }
		} elseif ($v['type'] == 'checks' && $user[$k] != 0) {
                $user[$k] = intval(get_checks_param($k));
                if ($user[$k] != 0)
				{
					$key = $k;
					if (substr($key, 0, 2) == "p_") $key = substr($key, 2);

                    $userSearchFilters[$k] = array(
                        'field' => $key,
                        'value' => get_param_array($k),
                    );

                    if ($k != 'p_star_sign') {
                        $where .= " AND " . to_sql($user[$k], 'Number') . " & (1 << (cast(i." . $key . " AS signed) - 1))";
                    }
				}
        } elseif ($v['type'] == 'checkbox' && $user[$k] != 0) {

                $params = get_param_array($k);
                foreach ($params as $key => $value) {
                    if ($value == 0) {
                        unset($params[$key]);
                    }
                }
                if (!empty($params)){
                    $userSearchFilters[$k] = array(
                        'field' => $k,
                        'value' => $params,
                    );
                    $nameTable = 'uck' . $numCheckbox;
                    $from_add .= " LEFT JOIN users_checkbox AS " . $nameTable . " ON " . $nameTable . ".user_id = u.user_id AND " . $nameTable . ".field = " . to_sql($v['id'], 'Number') . " AND "  . $nameTable . ".value IN (" . implode($params, ',') . ")";
                    $where .=  " AND " . $nameTable . ".user_id IS NOT NULL";
                    $numCheckbox++;
                }
        }
	}
}

if ($numCheckbox) {
    $from_group = 'u.user_id';
}

$paramUid = get_param('uid');
$onlyPhotos = Common::isOptionActive('no_profiles_without_photos_search');
$withPhoto = intval(get_param('with_photo'));

if ($display == 'encounters' || $display == 'rate_people') {
    $onlyPhotos = true;
} elseif ($onlyPhotos && !$withPhoto) {
    $onlyPhotos = false;
}
if ((get_param("photo", "") == "1" || $onlyPhotos) && (!$user['name_seo'] && !$paramUid)){
	$where .= " AND u.is_photo='Y'";
}

if (get_param("couple", "") == "1"){
	$where .= " AND u.couple='Y'";
}

$status = get_param('status');
if ($status == "online"){
    $time = date('Y-m-d H:i:s', time() - $g['options']['online_time'] * 60);
    $where .= ' AND u.last_visit> ' . to_sql($time, 'Text');
}
elseif ($status == "new"){
	$where .= ' AND u.register > ' . to_sql(date('Y-m-d H:00:00', (time() - $g['options']['new_time'] * 3600 * 24)), 'Text');
}
elseif ($status == "birthday"){
	$where .= " AND (DAYOFMONTH(u.birth)=DAYOFMONTH('" . date('Y-m-d H:i:s') . "') AND MONTH(u.birth)=MONTH('" . date('Y-m-d H:i:s') . "'))";
}


$day = to_sql(get_param('day', 0), 'Number');
$month = to_sql(get_param('month', 0), 'Number');
$year = to_sql(get_param('year', 0), 'Number');

if($day && $month && $year) {
    $month = sprintf('%02d', $month);
    $day = sprintf('%02d', $day);
    $where .= " AND u.register >= '$year-$month-$day 00:00:00' ";
}

$day = to_sql(get_param('day_to', 0), 'Number');
$month = to_sql(get_param('month_to', 0), 'Number');
$year = to_sql(get_param('year_to', 0), 'Number');

if($day && $month && $year) {
    $month = sprintf('%02d', $month);
    $day = sprintf('%02d', $day);
    $where .= " AND u.register <= '$year-$month-$day 23:59:59' ";
}


// IF active distance search, then exclude others
// DISTANCE
$distance = (int) get_param('radius', 0);
$user['city'] = (int) get_param("city", 0);
$user['state'] = (int) get_param("state", 0);
$user['country'] = (int) get_param("country", 0);
$peopleNearby = get_param_int('people_nearby');
$maxDistance = Common::getOption('max_search_distance');

$isFilterSocial = Common::isOptionActiveTemplate('list_users_filter_social');
$isActiveLocation = Common::isOptionActive('location_enabled', "{$optionTmplName}_join_page_settings");
$notActiveLocationFilterSocial = $isFilterSocial && !$isActiveLocation;
if ($notActiveLocationFilterSocial) {
    $peopleNearby = 0;
}


if(User::isSuperPowers()) {
    if ($peopleNearby) {
        $userLocation = array('country' => 0, 'state' => 0, 'city' => 0);
        
            $geoCityInfo = IP::geoInfoCity();
            $gUserCountryId = $geoCityInfo['country_id'];
            $gUserCityId = $geoCityInfo['city_id'];
            
        
        if($distance == 0){//In the whole city
            $whereLocation = " AND u.geo_position_city_id = " . to_sql($gUserCityId);
        } elseif (Common::getOption('max_filter_distance') == 'max_search_country' && $distance > $maxDistance) {//In the whole country
            $whereLocation = " AND u.geo_position_country_id = " . to_sql($gUserCountryId);
        } else {
            $whereLocation = getInRadiusWhere($distance);
        }

        
    } else {
        if($distance > $maxDistance && $user['city']>0) {
            $user['city'] = 0;
            $user['state'] = 0;
        }

        $allCountriesSearch = get_param_int('all_countries');
        if ($notActiveLocationFilterSocial) {
            $allCountriesSearch = 1;
        }
        if ($allCountriesSearch){
            $user['city'] = 0;
            $user['state'] = 0;
            $user['country'] = 0;
        }
        // search only by distance from selected city
        $whereLocation = '';
        if($distance && $user['city'])
        {
            // find MAX geo values
            $whereLocation = inradius($user['city'], $distance);
            $from_add .= " LEFT JOIN geo_city AS gc ON gc.city_id = u.city_id";
        } else {
            $whereLocation = Common::getWhereSearchLocation($user);
        }
    }
} else {
    $geoCityInfo = IP::geoInfoCity();
    $gUserCountryId = $geoCityInfo['country_id'];
    $gUserCityId = $geoCityInfo['city_id'];

    if($distance == 0){//In the whole city
        $whereLocation = " AND u.geo_position_city_id = " . to_sql($gUserCityId);
    } elseif (Common::getOption('max_filter_distance') == 'max_search_country' && $distance > $maxDistance) {//In the whole country
        $whereLocation = " AND u.geo_position_country_id = " . to_sql($gUserCountryId);
    } else {
        $whereLocation = getInRadiusWhere($distance);
    }
}
$from_add .= " LEFT JOIN userinfo AS i ON u.user_id=i.user_id ";
           //. " LEFT JOIN geo_city AS gc ON gc.city_id = u.city_id";

$keyword = trim(get_param("keyword", ""));
$search_header = get_param("search_header", "");
$where_search = '';
if ($keyword != "")
{
    if ($search_header == 1) {
        $where_search = ' OR u.mail =' . to_sql($keyword, 'Text');
    }
	$keyword_search_sql = "";
	$keyword = to_sql(strip_tags($keyword), "Plain");
	foreach ($g['user_var'] as $k => $v) if ($v['type'] == "text" or $v['type'] == "textarea") $keyword_search_sql .= " OR i." . $k . " LIKE '%" . $keyword . "%'";
	$where .= " AND (u.name LIKE '%" . $keyword . "%'" . $keyword_search_sql . $where_search . ") ";

}

//$ht = get_param("name", "") == "" ? "u.hide_time=0" : "1 ";

$ht = User::isHiddenSql();

if(get_param('moderator_view_profile')) {
    $ht = ' 1 ';
}

$wallLikesItemId = get_param_int('wall_item_id');
if($wallLikesItemId) {
    $whereSql = ' AND wl.wall_item_id = ' . to_sql($wallLikesItemId, 'Number');
    if ($optionTmplName == 'edge') {
        $where = $whereSql;
    } else {
        $where .= $whereSql;
    }
    $from_add .= ' LEFT JOIN wall_likes AS wl ON wl.user_id = u.user_id ';
    // show hidden profiles in likes
    $ht = ' 1 ';
} elseif ($show == 'photo_liked') {
    $photoId = get_param_int('photo_id');
    $where = ' AND wl.photo_id = ' . to_sql($photoId) . ' AND wl.like = 1';
    $from_add .= ' LEFT JOIN photo_likes AS wl ON wl.user_id = u.user_id ';
    $ht = ' 1 ';
} elseif ($show == 'video_liked') {
    $videoId = get_param_int('video_id');
    $where = ' AND wl.video_id = ' . to_sql($videoId) . ' AND wl.like = 1';
    $from_add .= ' LEFT JOIN vids_likes AS wl ON wl.user_id = u.user_id ';
    $ht = ' 1 ';
} elseif ($show == 'blogs_post_liked') {
    $blogId = get_param_int('blog_id');
    $where = ' AND BL.blog_id = ' . to_sql($blogId);
    $from_add .= ' LEFT JOIN blogs_post_likes AS BL ON BL.user_id = u.user_id ';
    $ht = ' 1 ';
}


if ($show == 'wall_shared') {
    $wallSharedItemId = get_param_int('wall_shared_item_id');
    $whereSql = " AND wl.section = 'share' AND item_id = " . to_sql($wallSharedItemId, 'Number');
    if ($optionTmplName == 'edge') {
        $where = $whereSql;
    } else {
        $where .= $whereSql;
    }
    $from_add .= ' LEFT JOIN wall AS wl ON wl.user_id = u.user_id ';
    $ht = ' 1 ';
} elseif($show == 'wall_liked_comment' || $show == 'photo_liked_comment'
            || $show == 'video_liked_comment' || $show == 'blogs_post_liked_comment') {
    $table = 'photo';
    if ($show == 'video_liked_comment') {
        $table = 'vids';
    } elseif ($show == 'wall_liked_comment') {
        $table = 'wall';
    } elseif ($show == 'blogs_post_liked_comment') {
        $table = 'blogs';
    }
    $likesCommentId = get_param_int('comment_id');
    $whereSql = ' AND lc.cid = ' . to_sql($likesCommentId, 'Number');
    if ($optionTmplName == 'edge') {
        $where = $whereSql;
    } else {
        $where .= $whereSql;
    }
    $from_add .= " LEFT JOIN {$table}_comments_likes AS lc ON lc.user_id = u.user_id ";
    $ht = ' 1 ';
}


$whereCore .= ' AND ' . $ht;

if (!$display) {
    if (Common::isOptionActive('users_ban_hide_in_the_search_results')) {
        $where .= ' AND u.ban_global = 0';
    }
    if(Common::isOptionActive('manual_user_approval') && Common::isOptionActive('users_no_approve_hide_in_the_search_results')) {
        $where .= ' AND `active` = 1';
    }
}


$uidsExclude = get_param('uids_exclude', '');
if($uidsExclude) {
    $where .= ' AND u.user_id NOT IN (' . to_sql($uidsExclude, 'Plain') . ') ';
}

$paramUid=false;
if (get_param('uid') != '') {
      $where = "u.user_id=" . intval(get_param('uid')) . "";
      $paramUid=true;
} else {

    $interest = get_param('interest');
    if($interest) {
        $from_add .= ' JOIN user_interests AS uint ON (u.user_id = uint.user_id AND uint.interest = ' . to_sql($interest) .') ';
    }

	$where = $ht . " " . $where . " ";
}

$order = '';

$orderNear = Common::getSearchOrderNear();

if ($withPhoto && $display != 'encounters' && $display != 'rate_people') {
    $order = "is_photo DESC, ";
}

if($user['i_am_here_to']) {
    Common::prepareSearchWhereOrderByIAmHereTo($where, $order, $user['i_am_here_to']);
}

if ($optionSet == 'urban') {
    if ($display == '') {
        $order .= ($isFreeSite) ? $orderNear . ' user_id DESC' : 'date_search DESC, ' . $orderNear . ' user_id DESC';
    }
} else {
    $order .= $orderNear . ' user_id DESC';
}

if ($isCustomShowList) {
    $order = ' date DESC';
}

if (Common::isOptionActive('do_not_show_me_in_search', 'template_options')
    && !$isCustomShowList
    && $g_user['user_id'] != User::getRequestUserId()) {
    $where .=" AND u.user_id != " . to_sql($g_user['user_id'], 'Number');
    $whereCore.=" AND u.user_id != " . to_sql($g_user['user_id'], 'Number');
}

if (get_param('join_search_page')){
    $where .=" AND u.is_photo_public = 'Y' ";
}
if ($g_user['user_id']) {
    $guidSql = to_sql($g_user['user_id'], 'Number');
    if ($optionSet == 'urban' && Common::isOptionActive('contact_blocking')
        && !$isCustomShowList && !Moderator::isAllowedViewingUsers()) {
        //$order = ' date_search DESC, near DESC,  user_id DESC';
        $isAllowYouToViewBlockedProfile = Common::isOptionActive('allow_you_to_view_blocked_profile', 'template_options');
        if (!$isAllowYouToViewBlockedProfile ||
            ($isAllowYouToViewBlockedProfile && !$user['name_seo'] && !get_param('uid'))) {
            $from_add .= " LEFT JOIN user_block_list AS ubl1 ON (ubl1.user_to = u.user_id AND ubl1.user_from = " . $guidSql . ")
                           LEFT JOIN user_block_list AS ubl2 ON (ubl2.user_from = u.user_id AND ubl2.user_to = " . $guidSql . ")";
            $where .=' AND ubl1.id IS NULL AND ubl2.id IS NULL';
            $whereCore.=' AND ubl1.id IS NULL AND ubl2.id IS NULL';
        }
    }
    if ($display == 'encounters') {
        //$uidEnc = get_param('uid');
        /*$where .=" AND u.is_photo_public = 'Y'
                   AND u.user_id != " . $guidSql .
                 ' AND ubl.id IS NULL';
        $from_add .= " LEFT JOIN user_block_list AS ubl ON (ubl.user_to = u.user_id AND ubl.user_from = " . $guidSql . ")
                                                        OR (ubl.user_from = u.user_id AND ubl.user_to = " . $guidSql . ")";*/

        $where .=" AND u.is_photo_public = 'Y'
                   AND u.user_id != " . $guidSql;
        //if ($uidEnc == '') {
        if (!$paramUid) {
        $where .=' AND enc1.user_from IS NULL AND enc2.user_from IS NULL ';
        /*
        $from_add .= " LEFT JOIN encounters AS enc ON (u.user_id = enc.user_to AND enc.user_from = " . $guidSql . ")
                                                   OR (u.user_id = enc.user_from AND enc.user_to = " . $guidSql . "
                                                       AND ((enc.from_reply != 'N' AND enc.to_reply != 'P')OR(enc.from_reply = 'N')))";
                                                       */
                $from_add .= " LEFT JOIN encounters AS enc1 ON (u.user_id = enc1.user_to AND enc1.user_from = " . $guidSql . ")
                               LEFT JOIN encounters AS enc2 ON (u.user_id = enc2.user_from AND enc2.user_to = " . $guidSql . "
                                                       AND ((enc2.from_reply != 'N' AND enc2.to_reply != 'P')OR(enc2.from_reply = 'N')))";

        }

        if ($isFreeSite) {
            $order .= 'near DESC,  user_id DESC';
        } else {
            $orderDate = $optionTmplName == 'impact' ? 'date_search' : 'date_encounters';
            $order .= $orderDate . ' DESC, near DESC,  user_id DESC';
        }

        if(!$paramUid && Users_List::isBigBase()) {
            Encounters::prepareFastSelect($where, $whereLocation, $from_add, $order);
            $addWhereLocation = false;
            $order = '';
        }

    }elseif ($display == 'rate_people') {
        /*$where .=" AND u.is_photo_public = 'Y'
                   AND u.user_id != " . $guidSql .
                 ' AND ubl.id IS NULL
                   AND upr.photo_id IS NULL ';
        $from_add .= ' LEFT JOIN user_block_list AS ubl ON (ubl.user_to = u.user_id AND ubl.user_from = ' . $guidSql . ')
                                                        OR (ubl.user_from = u.user_id AND ubl.user_to = ' . $guidSql . ')
                       LEFT JOIN photo AS up ON u.user_id = up.user_id AND up.private = "N"
                       LEFT JOIN photo_rate AS upr ON up.photo_id = upr.photo_id AND upr.user_id = ' . $guidSql;*/

        $where .=" AND u.is_photo_public = 'Y'
                   AND u.user_id != " . $guidSql .
                 ' AND upr.photo_id IS NULL ';
        $from_add .= ' LEFT JOIN photo AS up ON u.user_id = up.user_id AND up.private = "N" ' . CProfilePhoto::wherePhotoIsVisible('up') . '
                       LEFT JOIN photo_rate AS upr ON up.photo_id = upr.photo_id AND upr.user_id = ' . $guidSql;
        $order .= 'votes ASC, RAND()';
        $from_group = 'u.user_id';

    /*** ???????? ??????? ??? ???????? ???-?? ??????? *************************************/
        if(!$paramUid){
            if(Users_List::isBigBase()){
                /*
                $sql='CREATE TEMPORARY TABLE IF NOT EXISTS tmp_users(
                    `user_id` int(11) unsigned,
                    `i_am_here_to` tinyint(11) NOT NULL,
                    PRIMARY KEY (`user_id`)
                )';
                DB::execute($sql);
                */
                /*$sql='
                INSERT INTO tmp_users
                SELECT DISTINCT u.user_id, u.i_am_here_to
                 FROM user AS u '.$from_add. ' WHERE '.$where.' '.$whereLocation ;
                DB::execute($sql);
                $sql='
                    SELECT u.user_id, SUM(votes) AS votes, u.i_am_here_to
                    FROM tmp_users u
                    LEFT JOIN photo AS up ON u.user_id = up.user_id AND up.private = "N"
                    GROUP BY '.$from_group.'
                    ORDER BY '.$order.' LIMIT 0,1
                 ';*/

                //????????!!! ??? ????, ??????? ????? ??????? ? $order ?????????? ???????? ? SELECT

/* TOO SLOW ON BIG DATABASE
                $sql='
                    SELECT u.user_id, SUM(votes) AS votes, u.i_am_here_to
                    FROM (SELECT DISTINCT u.user_id, u.i_am_here_to
                        FROM user AS u '.$from_add. ' WHERE '.$where.' '.$whereLocation.') u
                    LEFT JOIN photo AS up ON u.user_id = up.user_id AND up.private = "N" ' . CProfilePhoto::wherePhotoIsVisible('up') . '
                    GROUP BY '.$from_group.'
                    ORDER BY '.$order.' LIMIT 0,1';
*/
                $where = User::getRatePhotoWhereOnBigBase($where, $from_add, $user, $whereLocation, $order);
                $addWhereLocation = false;

//                $whereRate = $where;
//                $whereIAmHereTo = false;
//
//                if($user['i_am_here_to']) {
//                    $whereRate .= ' AND i_am_here_to = ' . to_sql($user['i_am_here_to'], 'Number') . ' ';
//                    $whereIAmHereTo = true;
//                }
//
//                $sql = 'SELECT DISTINCT u.user_id
//                    FROM user AS u ' . $from_add . '
//                    WHERE ' . $whereRate . ' ' . $whereLocation . '
//                    LIMIT 100';
//
//                $users = DB::rows($sql);
//
//                if(!$users && $whereIAmHereTo) {
//                    $sql = 'SELECT DISTINCT u.user_id
//                        FROM user AS u ' . $from_add . '
//                        WHERE ' . $where . ' ' . $whereLocation . '
//                        LIMIT 100';
//                    $users = DB::rows($sql);
//                }
//
//                if($users) {
//                    shuffle($users);
//                }
//
//                $whereUid = isset($users[0]['user_id']) ? $users[0]['user_id'] : 0;
//                $where = ' u.user_id = ' . $whereUid . '
//                    AND upr.photo_id IS NULL';
                $order = '';
            }
        }
    /****************************************************/
    }

    /*$locations = array('country', 'state', 'city');
    foreach($locations as $location) {
        $userSearchFilters[$location] = array(
            'field' => $location,
            'value' => get_param($location),
        );
    }

    $userSearchFilters['radius'] = array(
        'field' => 'radius',
        'value' => get_param('radius'),
    );

    if($userSearchFiltersUpdate && $userSearchFilters) {
        $userSearchFiltersOrdered = array();
        // add filters order as param
        // TODO: move it to JS
        foreach($_GET as $key => $value) {
            if(isset($userSearchFilters[$key])) {
                $userSearchFiltersOrdered[$key] = $userSearchFilters[$key];
            }
        }

        $filter = json_encode($userSearchFiltersOrdered);
        //echo '<br><br>FILTER>' . $filter . '<br><br>';
        // update if new only
        if(guser('user_search_filters') != $filter) {
            $data = array(
                'user_search_filters' => $filter
            );
            User::update($data, guid(), 'userinfo');
        }
    }*/
}

/* Reset filter country */
$isResetFilter = false;
/*if($optionSet == 'urban' && $distance <= $maxDistance && $user['city']) {
    $total = DB::result('SELECT COUNT(u.user_id) FROM user AS u ' . $from_add . ' WHERE ' . $where . $whereLocation);
    if (!$total) {
        $user['city'] = 0;
        $user['state'] = 0;
        $whereLocation = Common::getWhereSearchLocation($user);
        $_GET['radius'] = intval(Common::getOption('max_search_distance')) + 1;
        $isResetFilter = true;
    }
}*/

if (($display == 'encounters' || $display == 'rate_people') && $paramUid) {
    $addWhereLocation = false;
} elseif ($isCustomShowList) {
    $addWhereLocation = false;
}
if ($addWhereLocation) {
    $where .= $whereLocation;
}
/* Reset filter country */

if ($g_user['user_id']) {
    $interest = get_param('interest');
    if ($interest) {
        $userSearchFilters['interest'] = array(
            'field' => 'interest',
            'value' => $interest,
        );
    }

    $userSearchFiltersMobile = array();
    $locations = array('country', 'state', 'city');
    foreach($locations as $location) {
        $userSearchFilters[$location] = array(
            'field' => $location,
            'value' => get_param($location),
        );
        $userSearchFiltersMobile[$location] = $userSearchFilters[$location];
    }

    $userSearchFilters['radius'] = array(
        'field' => 'radius',
        'value' => get_param('radius'),
    );
	$userSearchFiltersMobile['radius'] = $userSearchFilters['radius'];

    $userSearchFilters['people_nearby'] = array(
        'field' => 'people_nearby',
        'value' => get_param_int('people_nearby'),
    );
	$userSearchFiltersMobile['people_nearby'] = $userSearchFilters['people_nearby'];

    $userSearchFilters['all_countries'] = array(
        'field' => 'all_countries',
        'value' => get_param('all_countries',0),
    );

    $userSearchFilters['status'] = array(
        'field' => 'status',
        'value' => $status,
    );
    $userSearchFiltersMobile['status'] = $userSearchFilters['status'];

    $userSearchFilters['with_photo'] = array(
        'field' => 'with_photo',
        'value' => $withPhoto,
    );
    $userSearchFiltersMobile['with_photo'] = $userSearchFilters['with_photo'];

    if(($userSearchFiltersUpdate || $isResetFilter) && $userSearchFilters) {
        $userSearchFiltersOrdered = array();
        // add filters order as param
        // TODO: move it to JS
        foreach($_GET as $key => $value) {
            if(isset($userSearchFilters[$key])) {
                $userSearchFiltersOrdered[$key] = $userSearchFilters[$key];
            }
        }

        $filter = json_encode($userSearchFiltersOrdered);
        //echo '<br><br>FILTER>' . $filter . '<br><br>';
        // update if new only
        if(guser('user_search_filters') != $filter) {
            User::updateParamsFilter('user_search_filters', $filter);
            $userSearchFiltersMobile = json_encode($userSearchFiltersMobile);
            if (guser('user_search_filters_mobile') != $userSearchFiltersMobile) {
                User::updateParamsFilter('user_search_filters_mobile', $userSearchFiltersMobile);
            }
            /*$data = array(
                'user_search_filters' => $filter
            );
            $userSearchFiltersMobile = json_encode($userSearchFiltersMobile);
            if (guser('user_search_filters_mobile') != $userSearchFiltersMobile) {
                $data['user_search_filters_mobile'] = $userSearchFiltersMobile;
            }

            User::update($data, guid(), 'userinfo');*/
        }

        /*if(get_cookie('settings_filter_search') != $filter) {
            set_cookie('settings_filter_search', $filter);
        }
        #echo get_cookie('settings_filter_search');*/
    }
}

$global_username_search=get_param('global_search_by_username');
$redirectIfSingle=false;
if(trim($global_username_search)!=''){
    $where=$whereCore.' AND u.name LIKE "%'.to_sql($global_username_search,'Plain').'%"';
    $redirectIfSingle=true;
}

if ($optionTmplName == 'edge') {
    if ($show == 'wall_shared' || $show == 'wall_liked') {
        $order = 'wl.id DESC, is_photo DESC,  near DESC,  user_id DESC';
    } elseif ($show == 'wall_liked_comment' || $show == 'photo_liked_comment'
                || $show == 'video_liked_comment' || $show == 'blogs_post_liked_comment') {
        $order = 'lc.id DESC, is_photo DESC,  near DESC,  user_id DESC';
    } elseif ($show == 'blogs_post_liked') {
        $order = 'BL.id DESC, is_photo DESC,  near DESC,  user_id DESC';
    }
}
$page = Users_List::show($where, $order, $from_add, '', $from_group,$redirectIfSingle);

include("./_include/core/main_close.php");