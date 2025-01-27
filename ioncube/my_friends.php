<?php
/* (C) Websplosion LTD., 2001-2014

IMPORTANT: This is a commercial software product
and any kind of using it must agree to the Websplosion's license agreement.
It can be found at http://www.chameleonsocial.com/license.doc

This notice may not be removed from the source code. */

$area = "login";
include("./_include/core/main_start.php");
include("./_include/current/friends.php");

CustomPage::setSelectedMenuItemByTitle('column_narrow_can_see_your_private_photos');

class CPhoto extends CHtmlBlock
{
    public $responseData = false;

    function action()
    {
        global $g;

        $cmd = get_param('cmd');
        $this->responseData = User::friendAction();
        if(get_param('ajax_data')) {
            $data = $this->responseData;
            if (get_param('get_counter_pending')) {
                if (Common::isOptionActiveTemplate('groups_social_enabled')) {
                    $counter = TemplateEdge::getNumberFriendsAndSubscribersPending();
                } else {
                    $counter = User::getNumberRequestsToFriendsPending();
                }
                $data = array('action' => $data,
                              'counter' => $counter
                        );
                $uid = get_param_int('friends_list_uid');
                if ($uid) {
                    $data['list_friends'] = TemplateEdge::getListFriends($uid);
                    $data['list_friends_online'] = TemplateEdge::getListFriends(null,true);
                    $data['wall_only_post'] = User::getInfoBasic($uid, 'wall_only_post');
                }
            }
            die(getResponseDataAjaxByAuth($data));
        }elseif ($cmd == 'send_request_private_access') {
            $responseData = CIm::sendRequestPrivateAccess();
        }
    }

    function getSql($show, $customWhere = '')
	{
        global $g_user;

        if ($customWhere) {
            $customWhere = " WHERE {$customWhere}";
        }

        $guidSql = to_sql($g_user['user_id']);
        if ($show == 'all') {
            $sql = "SELECT * FROM(
                    SELECT FR.*, @rownum := @rownum + 1 AS `rank`
                      FROM
                    (SELECT *
                      FROM `friends_requests`
                     WHERE (`user_id` = {$guidSql} OR `friend_id` = {$guidSql})
                       AND `accepted` = 1
                     ORDER BY `activity` DESC, `created_at` ASC, `user_id` ASC, `friend_id` ASC) FR,
                    (SELECT @rownum := 0) R) AFR {$customWhere}";
        }elseif ($show == 'all_and_pending') {
            $sql = "SELECT * FROM(
                    SELECT FR.*, @rownum := @rownum + 1 AS `rank`
                      FROM
                    ((SELECT *
                       FROM `friends_requests`
                      WHERE user_id = {$guidSql}
                        AND `accepted` = 1)
                    UNION
                    (SELECT *
                       FROM `friends_requests`
                      WHERE friend_id = {$guidSql}
                        AND `accepted` = 1)
                      UNION
                    (SELECT *
                       FROM `friends_requests`
                      WHERE `friend_id` = {$guidSql}
                        AND `accepted` = 0)
                     ORDER BY `accepted` ASC, `created_at` DESC, `activity` DESC) FR,
                     (SELECT @rownum := 0) R) AFR {$customWhere}";
        }
        return $sql;
    }

    static function getShow() {
        $show = get_param('show', 'all');
        $optionSetTemplate = Common::getOptionTemplate('set');
        $optionNameTemplate = Common::getOptionTemplate('name');
        if ($optionNameTemplate == 'edge') {
            if (!in_array($show, array('all', 'online'))) {
                $show = 'all';
            }
        }elseif ($optionSetTemplate == 'urban' && !in_array($show, array('all', 'all_and_pending'))) {
            $show = 'all_and_pending';
        }
        return $show;
    }

	function parseBlock(&$html)
	{
		global $g;
		global $g_user;

        $optionNameTemplate = Common::getOption('name', 'template_options');
        $html->setvar('display', User::displayProfile());
        if(Common::isOptionActive('invite_friends')) {
            $html->parse('invite_on');
        }
		$start = get_param_int('start', get_param_int('offset'));
        $uid = get_param_int('uid');
		//$show = get_param('show', 'all');

        $show = self::getShow();

		$html->setvar($show, '_active');
		$html->setvar('show', $show);

		$eu = ($start - 0);

        $customLimit = Common::getOption('user_custom_per_page', 'template_options');
        $limit = ($customLimit) ? $customLimit : 10;

        $html->setvar('on_page', $limit);

        $customWhere = '';
        $isCustomList = Common::isOptionActive('list_users_my_friends_tmpl_parts', 'template_options');
        $isAjaxRequest = get_param('ajax');
        if ($isCustomList && $isAjaxRequest) {
            $limit = get_param('on_page');
            $customWhere = '`rank` > ' . to_sql(get_param('rank', 0));
        }

		$this_page = $eu + $limit;
		$back = $eu - $limit;
		$next = $eu + $limit;

        $guid = $g_user['user_id'];
        $guidSql = to_sql($g_user['user_id']);
        if ($optionNameTemplate == 'edge') {
            $uid = User::getParamUid(0);
            if (!$uid) {
                redirect(Common::pageUrl('user_friends_list', $guid));
            }
            $guid = $uid;
            $guidSql = to_sql($uid);
        }

        $nume = 0;
		if (($show == 'all' || $show == 'online') && !$isCustomList){
            if ($show == 'online') {
                $nume = User::getNumberFriendsOnline($guid);
            } else {
                $sql = 'SELECT *
                          FROM `friends_requests`
                         WHERE (`user_id` = ' . $guidSql . ' OR `friend_id` = ' . $guidSql . ')
                           AND accepted = 1
                         ORDER BY activity DESC';
                $result = DB::query($sql);
                $nume = DB::num_rows();
            }
			$html->setvar('num_users', $nume);
			$html->parse('all_friends', true);
		} elseif ($show=="recently") {
			$result=DB::query("SELECT * FROM friends_requests WHERE (user_id='".$g_user['user_id']."' OR friend_id='".$g_user['user_id']."') AND accepted=1 ORDER BY created_at DESC LIMIT 0,10");
			$nume=DB::num_rows();
			$html->setvar("num_users", $nume);
			$html->parse("recently_friends", true);
		}
		elseif ($show=="requests")
		{
			$result=DB::query("SELECT * FROM friends_requests WHERE friend_id='".$g_user['user_id']."' AND accepted=0 ORDER BY created_at DESC");
			$nume=DB::num_rows();
			$html->setvar("num_users", $nume);
			$html->parse("requests_friends", true);
		}
		elseif ($show=="birthdays")
		{
            $dateEnd    = explode('-', date('Y-m-d', strtotime('+14 day')));
            $monthFirst = date('m');
            $dayFirst   = date('d');
            $monthLast  = $dateEnd[1];
            $dayLast    = $dateEnd[2];
            $numberDayMonth = date("t");

            $sqlAdd = '';
            if ($monthFirst != $monthLast) {
                $sqlAdd = " OR (MONTH(U.birth) = $monthLast AND DAY(U.birth) BETWEEN 1 AND $dayLast)";
                $dayLast = ($monthFirst == 2) ? $numberDayMonth + 1 : $numberDayMonth;
            }

            $sqlBirthdays = "SELECT F.*
                               FROM `friends_requests` AS F,
                                    `user` AS U
                              WHERE F.accepted = 1
                                AND ((F.user_id = " . to_sql($g_user['user_id'], 'Number') . " AND F.friend_id = U.user_id)
                                      OR
                                     (F.friend_id = " . to_sql($g_user['user_id'], 'Number') . " AND F.user_id = U.user_id))
                                AND  ((MONTH(U.birth) = $monthFirst AND DAY(U.birth) BETWEEN $dayFirst AND $dayLast ) $sqlAdd)
                              ORDER BY U.birth DESC";
			$result = DB::query($sqlBirthdays);
			$nume = DB::num_rows();
			$html->setvar('num_users', $nume);
			$html->parse('birthdays_friends', true);
		}

        $pageUrl = '';
        if ($optionNameTemplate == 'edge') {
            if ($show == 'online') {
                $pageDescription = l('your_friends_who_are_currently_online');
                $pageTitle = l('friends_online');
                $pageUrl = Common::pageUrl('my_friends_online');
            } else {
                $pageTitle = l('your_friends');
                $pageUrl = Common::pageUrl('user_friends_list');
                $pageDescription = l('people_you_have_mutually_added_to_friends');
                if ($guid != guid()) {
                    $name = User::getInfoBasic($guid, 'name');
                    $name = User::nameShort($name);
                    $pageTitle = lSetVars('page_title_someones', array('name' => $name));
                    $pageDescription = l('see_if_you_have_mutual_friends');
                }
            }
            $vars = array('page_title'   => $pageTitle,
                          'page_description' => $pageDescription,
                          'url_pages'    => $pageUrl,
                          'page_number'  => $start,
                          'page_user_id' => $guid,
                          'page_my_friends' => intval($guid == guid()),
                          'page_param'   => 'offset',
                          'page_type' => 'friends',
                          'page_filter' => 0,
                          'page_guid' => $guid,
                    );
            $html->assign('', $vars);

            if (!$isAjaxRequest) {
                TemplateEdge::parseColumn($html, $guid);
            }
        }

		if ($nume > 0 || $isCustomList)
		{
            if ($isCustomList) {
                $sql = $this->getSql($show, $customWhere) . ' LIMIT ' . to_sql($limit + 1, 'Number');
                DB::query($sql, 2);
                $html->setvar('stop', intval(DB::num_rows(2) == ($limit + 1)));
                $sql = $this->getSql($show, $customWhere) . ' LIMIT ' . to_sql($limit, 'Number');
                DB::query($sql);
            } elseif ($show == "all" || $show == 'online') {
                if ($show == 'online') {
                    $whereOnline = ' AND U.last_visit > ' . to_sql(date('Y-m-d H:i:s', time() - Common::getOption('online_time') * 60)) . ' ';
                    $sql = 'SELECT *
                              FROM `friends_requests` AS F
                              LEFT JOIN `user` AS U ON U.user_id = IF(F.user_id = ' . $guidSql . ', F.friend_id, F.user_id)
                             WHERE F.accepted = 1 '  . $whereOnline .
                             ' AND (F.user_id = ' . $guidSql . ' OR F.friend_id = ' . $guidSql . ')
                             ORDER BY F.activity DESC, F.created_at DESC, F.user_id DESC, F.friend_id DESC
                             LIMIT ' . to_sql($eu, 'Number') . ", " . to_sql($limit, 'Number');;
                } else {
                    $sql = 'SELECT *
                              FROM `friends_requests`
                             WHERE (`user_id` = ' . $guidSql . ' OR `friend_id` = ' . $guidSql . ')
                               AND accepted = 1
                         ORDER BY activity DESC, created_at DESC, user_id DESC, friend_id DESC
                         LIMIT ' . to_sql($eu, 'Number') . ", " . to_sql($limit, 'Number');
                }
                $result = DB::query($sql);
				$on_this_page = DB::num_rows();
            } elseif ($show=="requests") {
				$result=DB::query("SELECT * FROM friends_requests WHERE friend_id='".$g_user['user_id']."' AND accepted=0 ORDER BY created_at LIMIT ".to_sql($eu, "Number").", ".to_sql($limit, "Number")."");
				$on_this_page=DB::num_rows();
			}
			elseif ($show=="recently")
			{
				$result=DB::query("SELECT * FROM friends_requests WHERE (user_id='".$g_user['user_id']."' OR friend_id='".$g_user['user_id']."') AND accepted=1 ORDER BY created_at DESC LIMIT 0,10");
				$on_this_page=DB::num_rows();
			}
			elseif ($show=="birthdays")
			{
				$sql = $sqlBirthdays . ' LIMIT ' . to_sql($eu, 'Number') . ', ' . to_sql($limit, 'Number');
                $result = DB::query($sql);
				$on_this_page = DB::num_rows();
			}

            if (!$isCustomList) {
                $pageLimit = Common::getOptionTemplateInt('usersinfo_pages_per_list');
                if (!$pageLimit) {
                    $pageLimit = 5;
                }
                Common::parsePagesList($html, 'top', $nume, $start, $limit, $pageLimit, $pageUrl);
            }

			$sep = 0;
            $i = 0;
            $isContactBlocking = Common::isOptionActive('contact_blocking');
            $blockLock = 'user_lock';
            $blockGifts = 'gifts_enabled';

            $profileDisplayType = Common::getOption('list_people_display_type', 'edge_general_settings');
            $numberRow = Common::getOptionInt('list_people_number_row', 'edge_general_settings');

            $nume = DB::num_rows();
            if ($nume > 0) {
                while ($row = DB::fetch_row())
                {
                    $haveFriends = true;
                    $friend_id = isset($row['fr_user_id']) ? $row['fr_user_id'] : (($row['user_id'] == $guid) ? $row['friend_id'] : $row['user_id']);
                    $row_user = User::getInfoBasic($friend_id, false, 2);

                    if ($optionNameTemplate == 'edge') {
                        TemplateEdge::parseUser($html, $row_user, $numberRow, $profileDisplayType, 'users_list_item');
                        $html->parse('users_list_item');
                        continue;
                    }

                    $sizePhoto = 'r';
                    if ($optionNameTemplate == 'impact') {
                        $sizePhoto = 'm';
                    }
                    $user_photo = User::getPhotoDefault($friend_id, $sizePhoto);

                    $html->setvar("user_name", $row_user['name']);
                    if ($html->varExists('name_one_letter_short')) {
                        $html->setvar('name_one_letter_short', User::nameOneLetterShort($row_user['name']));
                    }
                    if ($html->varExists('user_gender')) {
                        $html->setvar('user_gender', $row_user['gender'] == 'M' ? l('man') : l('woman'));
                    }

                    if ($html->varExists('user_orientation')) {
                        $orientationTitle = '';

                        $orientationInfo = User::getOrientationInfo($row_user['orientation']);
                        if(isset($orientationInfo['title'])) {
                            $orientationTitle = l($orientationInfo['title']);
                        }
                        $html->setvar('user_orientation', $orientationTitle);
                    }

                    $html->setvar("user_photo", $user_photo);
                    $html->setvar("user_id", $row_user['user_id']);
					$html->setvar("user_profile_link", User::url($row_user['user_id'],$row_user));
                    $html->setvar("age", $row_user['age']);

                    if ($html->varExists('rank') && isset($row['rank'])) {
                        $html->setvar('rank', $row['rank']);
                    }
                    if ($html->blockexists('user_pending')) {
                        $isPending = 0;
                        if ($row['accepted']) {
                            $html->setvar('action_remove', l('unfriend'));
                            $html->clean('user_pending');
                            $html->clean('user_approve_friend');
                            $html->parse('user_friend', false);
                        } else {
                            $html->clean('user_friend');
                            $isPending = 1;
                            $html->setvar('action_remove', l('remove'));
                            $html->setvar('created_time_ago', timeAgo($row['created_at'], 'now', 'string', 60, 'second'));
                            $html->parse('user_pending', false);
                            $html->parse('user_approve_friend', false);
                        }
                        $html->setvar('is_pending', $isPending);
                    }

                    if ($html->blockexists($blockLock) && $isContactBlocking) {
                        $html->parse($blockLock, false);
                    }
                    if ($html->blockExists($blockGifts) && Common::isOptionActive($blockGifts)) {
                        $html->parse($blockGifts, false);
                    }

                    if ($show == 'birthdays') {
                        $date = explode('-', $row_user['birth']);
                        $month = Common::listMonths('birth_');
                        $day = $date[2];
                        $day = ($day[0] == 0) ? $day[1] : $day;
                        $birthDate = lSetVars('birth_template', array('day' => $day,
                                                                      'month' => $month[intval($date[1])]));
                        $html->setvar('birth', ', ' . $birthDate);
                    } else {

                        if (!empty($row_user['country'])) {
                            $html->setvar('country', l($row_user['country']));
                            $html->parse('country_on', false);
                        } else {
                            $html->setblockvar('country_on', '');
                        }

                        if (!empty($row_user['state'])) {
                            $html->setvar('state', l($row_user['state']));
                            $html->parse('state_on', false);
                        } else {
                            $html->setblockvar('state_on', '');
                        }

                        if (!empty($row_user['city'])) {
                            $html->setvar('city', l($row_user['city']));
                            $html->parse('city_on', false);
                        } else {
                            $html->setblockvar('city_on', '');
                        }
                    }

                    if($show == "requests")
                    {
                        if(trim($row['message']) != '') {
                            $html->setvar("message", he(strcut($row['message'], 30)));
                            $html->setvar("message_full", he($row['message']));
                            $html->parse("item_block_users_message", false);
                        } else {
                            $html->setblockvar('item_block_users_message', '');
                        }
                        $html->parse("item_block_users_requests", false);
                    }
                    else
                    {
                        $html->parse("item_block_users_all", false);
                    }

                    $sep++;
                    if ($sep==2)
                    {
                        $html->parse("sep_block_users", true);
                        $sep=0;
                    }
                    if (!$start && !$isAjaxRequest) {
                        $html->subcond($i++, 'border_top', 'border_none');
                    } else {
                        $html->parse('border_top', false);
                    }

                    if (is_array($row_user)) $html->parse("item_block_users", true);
                }
                $html->parse("block_users", true);

                $block = "list_people_{$profileDisplayType}";
                if ($html->blockExists($block)) {
                    $html->parse($block, false);
                }

            } else {
                if ($html->blockexists('no_one_here_yet')){
                    $html->parse('no_one_here_yet');
                }

            }
		} else {
            if ($html->blockexists('list_noitems')){
                $html->parse('list_noitems');
            }
			if ($show=="all") {
                if ($html->blockexists('no_one_here_yet')){
                    $html->parse('no_one_here_yet');
                }
				$html->parse("error_all_users", true);
			} elseif ($show=="recently") {
				$html->parse("error_recently_users", true);
			} elseif ($show=="birthdays") {
				$html->parse("error_birthdays_users", true);
			}
		}

        if ($html->blockExists('class_all')) {
            $html->cond($show == 'all_and_pending', 'class_all_pending', 'class_all');
        }
        if ($html->varExists('title_page')) {
            $html->setvar('title_page', $show == 'all' ? l('column_narrow_can_see_your_private_photos') : l('column_narrow_friends'));
        }

		parent::parseBlock($html);
	}
}


$isAjaxRequest = get_param('ajax');
$optionNameTemplate = Common::getOption('name', 'template_options');

$listTmpl = getPageCustomTemplate('my_friends.html', 'my_friends_template');

if (Common::isOptionActive('list_users_my_friends_tmpl_parts', 'template_options')) {
    $listTmpl = array(
        'main' => $g['tmpl']['dir_tmpl_main'] . 'my_friends.html',
        'items' => $g['tmpl']['dir_tmpl_main'] . '_my_friends_items.html',
    );
    if (CPhoto::getShow() == 'all_and_pending') {
        $listTmpl['items'] = $g['tmpl']['dir_tmpl_main'] . '_my_friends_and_pending_items.html';
    }
    if($isAjaxRequest) {
        $listTmpl['main'] = $g['tmpl']['dir_tmpl_main'] . 'search_results_ajax.html';
    }
}elseif ($isAjaxRequest && $optionNameTemplate == 'edge') {
    $listTmpl['main'] = $g['tmpl']['dir_tmpl_main'] . 'search_results_ajax.html';
    unset($listTmpl['profile_column_left']);
    unset($listTmpl['profile_column_right']);
}

$page = new CPhoto('', $listTmpl);

if($isAjaxRequest) {
    getResponsePageAjaxByAuthStop($page);
}

$header = new CHeader('header', $g['tmpl']['dir_tmpl_main'] . '_header.html');
$page->add($header);
$footer = new CFooter('footer', $g['tmpl']['dir_tmpl_main'] . '_footer.html');
$page->add($footer);

if (Common::isParseModule('profile_menu')) {
    $show = get_param('show', 'all');
    $friends_menu = new CFriendsMenu('friends_menu', $g['tmpl']['dir_tmpl_main'] . '_friends_menu.html');
    if($show == 'recently')
        $friends_menu->active_button = 'recent';
    if($show == 'birthdays')
        $friends_menu->active_button = 'birthdays';
    if($show == 'requests')
        $friends_menu->active_button = 'pending';
    $page->add($friends_menu);
}

if (Common::isParseModule('profile_colum_narrow')){
    $column_narrow = new CProfileNarowBox('profile_column_narrow', $g['tmpl']['dir_tmpl_main'] . '_profile_column_narrow.html');
    $page->add($column_narrow);
}

include("./_include/core/main_close.php");