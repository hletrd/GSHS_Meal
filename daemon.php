<?php
define("COOKIE_c_user", '');
define("COOKIE_xs", '');
define("pageurl", 'gshsmeal');
define("APIKEY", '201300000001');
define("offset", 0);
require_once('TwitterAPIExchange.php');
$settings = array(
);

date_default_timezone_set('Asia/Seoul');

//$allergy = array('①', '②', '③', '④', '⑤', '⑥', '⑦', '⑧', '⑨', '⑩', '⑪', '⑫', '⑬');
$allergy = array( '10.', '11.', '12.', '13.', '1.', '2.', '3.', '4.', '5.', '6.', '7.', '8.', '9.');
$allergy_desc = array('난류', '우유', '메밀', '땅콩', '대두', '밀', '고등어', '게', '새우', '돼지고기', '복숭아', '토마토', '아황산염');
$allergy_desc = array('⑩', '⑪', '⑫', '⑬', '①', '②', '③', '④', '⑤', '⑥', '⑦', '⑧', '⑨');
$delicious = array('비요뜨' => '삐요뜨', '허니버터아몬드' => '허니버터아몬드', '뚝심햄구이' => '뚝심햄구이', '잉글리쉬머핀' => '잉글리쉬머핀(맥모닝)', '후룻볼' => '후룻볼', '김구이' => '김구이', '크로슈무슈' => '크로슈무슈', '시리얼' => '시리얼', '초코머핀' => '초코머핀');
$weekdays = array('일', '월', '화', '수', '목', '금', '토');

foreach($allergy_desc as &$i) {
	$i = '(' . $i . ', ) ';
}

define("TYPE_NONE", 1);
define("TYPE_BREAKFAST", 2);
define("TYPE_BREAKFAST_ALWAYS", 3);
define("TYPE_LUNCH", 4);
define("TYPE_DINNER", 5);
define("TYPE_ALL", 6);
define("TYPE_GANSIK", 7);

$type = TYPE_DINNER;

$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'foodbot');

$ch_fb = curl_init();
curl_setopt($ch_fb, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch_fb, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch_fb, CURLOPT_COOKIE, 'xs=' . COOKIE_xs . '; c_user=' . COOKIE_c_user);
curl_setopt($ch_fb, CURLOPT_USERAGENT, 'foodbot');
curl_setopt($ch_fb, CURLOPT_FOLLOWLOCATION, true);

function fb_post($text) {
	curl_setopt($GLOBALS['ch_fb'], CURLOPT_URL, 'https://m.facebook.com/' . pageurl . '?soft=composer');
	$data = curl_exec($GLOBALS['ch_fb']);
	$data = explode('<form', $data)[2];
	$data = explode('</form>', $data)[0];
	$link = explode('action="', $data)[1];
	$link = explode('"', $link)[0];
	$link = 'https://m.facebook.com' . $link;

	$inputs = explode('<input', $data);
	unset($inputs[0]);
	$post = '';
	foreach($inputs as $i) {
		$type = explode('type="', $i)[1];
		$type = explode('"', $type)[0];
		if ($type == 'hidden') {
			$key = explode('name="', $i)[1];
			$key = explode('"', $key)[0];
			$val = explode('value="', $i)[1];
			$val = explode('"', $val)[0];
			if ($key == 'charset_test') {
				$post .= $key . '%E2%82%AC%2C%C2%B4%2C%E2%82%AC%2C%C2%B4%2C%E6%B0%B4%2C%D0%94%2C%D0%84&';
			} else {
				$post .= $key . '=' . htmlspecialchars($val) . '&';
			}
		}
	}
	$post .= 'xc_message=' . $text;
	$post .= '&view_post=Post';
	$link = htmlspecialchars_decode($link);
	curl_setopt($GLOBALS['ch_fb'], CURLOPT_URL, $link);
	curl_setopt($GLOBALS['ch_fb'], CURLOPT_POST, true);
	curl_setopt($GLOBALS['ch_fb'], CURLOPT_POSTFIELDS, $post);
	curl_exec($GLOBALS['ch_fb']);
	echo 'Posted(' . date("Y-m-d H:i:s") . '): ' . $text . "\n";
}

function tw_post($status) {
	$fields = array(
		'status' => $status,
	);
	$url = 'https://api.twitter.com/1.1/statuses/update.json';
	$method = 'POST';
	
	$twitter = new TwitterAPIExchange($GLOBALS['settings']);
	$response = $twitter->buildOauth($url, $method)->setPostfields($fields)->performRequest();
	echo "Uploaded one!\n";
	//var_dump(json_decode($response));
}
echo "Daemon started...\n";
while(1) {
	unset($food);
	for($i = 0; $i < 5; $i++) {
		$food[$i] = array();
		curl_setopt($ch, CURLOPT_URL, 'http://student.gs.hs.kr/student/api/meal/meal.do?key=' . APIKEY . '&month=' . date('Ym', time() + 86400 * $i + 86400 * offset) . '&date=' . date('d', time() + 86400 * $i + 86400 * offset));
		$data = curl_exec($ch);
		$data = json_decode($data, true)['meal']['data'][0];
		foreach($data as $key=>$val) {
			if ($key !== 'date') array_push($food[$i], $val);
		}
	}

	foreach($food as &$i) {
		foreach($i as &$j) {
			$j = str_replace($allergy, $allergy_desc, $j);
			$j = str_replace(') (', '', $j);
			$j = str_replace(', )', ')', $j);
			$j = str_replace('|', "\n* ", $j);
			$j = preg_replace('/[0-9]+\\(/', '(', $j);
			$j = preg_replace('/\\) [0-9]+/', ')', $j);
			$j = preg_replace('/[0-9]+\\n/', "\n", $j);
			$j = preg_replace('/[0-9]+$/', "", $j);
			$j = '* ' . $j;
			if (trim($j) == '*') $j = '정보 없음';
		}
	}

	$result_final = '';
	switch ($type) {
		case TYPE_BREAKFAST:
			echo "Posting breakfast\n";
			foreach($delicious as $key => $val) {
				if (mb_strpos($food[0][0], $key) !== false) {
					$result_final = '오늘 아침에 여러분들 좋아하는 ' . $val . ' 나왔습니다. 꼭 아침식사하시고 등교하시기 바랍니다.';
					break;
				}
			}
			if ($result_final == '' && (mb_strpos($food[0][0], '스프') !== false || mb_strpos($food[0][0], '우유') !== false)) {
				$result_final = '오늘 아침이 맛있을 것으로 추정됩니다. 꼭 아침식사하시고 등교하시기 바랍니다.';
			}
			if ($result_final !== '') tw_post($result_final);
			break;
		case TYPE_BREAKFAST_ALWAYS:
			echo "Posting breakfast_always\n";
			if ($food[0][0] === '정보 없음') break;
			$result_final = "- " . date("m-d") . " 아침\n";
			$result_final .= $food[0][0];
			tw_post($result_final);
			break;
		case TYPE_LUNCH:
			echo "Posting lunch\n";
			if ($food[0][1] === '정보 없음') break;
			$result_final = "- " . date("m-d") . " 점심\n";
			$result_final .= $food[0][1];
			tw_post($result_final);
			break;
		case TYPE_DINNER:
			echo "Posting dinner\n";
			if ($food[0][2] === '정보 없음') break;
			$result_final = "- " . date("m-d") . " 저녁\n";
			$result_final .= $food[0][2];
			tw_post($result_final);
			break;
		case TYPE_ALL:
			unset($food[0]);
			echo "Posting all\n";
			foreach($food as $key=>$val) {
				if ($result_final != '') $result_final .= "\n--------------------\n";
				$result_final .= date('m월 d일 ' . $weekdays[date('w', time() + 86400 * $key)] . '요일 급식', time() + 86400 * $key);
				$result_final .= "\n";
				$result_final .= '-- 아침 --' . "\n" . $val[0] . "\n";
				$result_final .= '-- 점심 --' . "\n" . $val[1] . "\n";
				$result_final .= '-- 저녁 --' . "\n" . $val[2] . "\n";
			}
			tw_post($result_final);
			break;
		default:
	}
	echo "Waiting...";

	$type = TYPE_NONE;


	$time = time() + 3600 * 9;

	while(!($time % 86400 === 25160 || $time % 86400 === 25170 || $time % 86400 === 39600 || $time % 86400 === 39610 || $time % 86400 === 25200 || $time % 86400 === 25210 || $time % 86400 === 57600 || $time % 86400 === 57610 /* || $time % 86400 === 75600 || $time % 86400 === 68400 */)) {
		usleep(400000);
		$time = time() + 3600 * 9;
	}
	if ($time % 86400 === 25160 || $time % 86400 === 25170) {
		$type = TYPE_BREAKFAST_ALWAYS;
	} else if ($time % 86400 === 25200 || $time % 86400 === 25210) {
		$type = TYPE_BREAKFAST;
	} else if ($time % 86400 === 39600 || $time % 86400 === 39610) {
		$type = TYPE_LUNCH;
	} else if ($time % 86400 === 57600 || $time % 86400 === 57610) {
		$type = TYPE_DINNER;
	}/* else if ($time % 86400 === 75600) {
		$type = TYPE_ALL;
	} else if ($time % 86400 === 68400) {
		$type = TYPE_GANSIK;
	}*/
}