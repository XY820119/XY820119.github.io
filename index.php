<?php
session_start();
header('Content-Type:text/html;charset=utf-8');
date_default_timezone_set('Asia/Shanghai');

$SERVER_AK = "ue2P8gdLLJUczOTGDIljgLqYge00foEf";
$BROWSER_AK = "ll630dgO9mlsB7v0QHvzRriNr4VbYT49";
$DEEPSEEK_KEY = "sk-edff83b94a47427daa2bdc16bbdca9ea";

function getRealIP(){
    $keys = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_CLIENT_IP',
        'REMOTE_ADDR'
    ];

    foreach($keys as $k){
        if(!empty($_SERVER[$k])){
            $ips = explode(',',$_SERVER[$k]);

            foreach($ips as $ip){
                $ip = trim($ip);

                if(filter_var($ip,FILTER_VALIDATE_IP)){
                    return $ip;
                }
            }
        }
    }

    return '0.0.0.0';
}

function curlGet($url){

    $ch = curl_init();

    curl_setopt_array($ch,[
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => [
            'User-Agent: Mozilla/5.0'
        ]
    ]);

    $res = curl_exec($ch);

    curl_close($ch);

    return $res;
}

function curlPost($url,$data,$headers=[]){

    $ch = curl_init();

    curl_setopt_array($ch,[
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data,JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER => array_merge([
            'Content-Type: application/json'
        ],$headers),
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 60
    ]);

    $res = curl_exec($ch);

    curl_close($ch);

    return $res;
}

$ip = getRealIP();

$data = [
    'ip' => $ip,
    'country' => 'Unknown',
    'province' => 'Unknown',
    'city' => 'Unknown',
    'district' => 'Unknown',
    'address' => 'Unknown',
    'lng' => '0',
    'lat' => '0',
    'timezone' => 'UTC',
    'offset' => '+0',
    'operator' => 'Unknown',
    'network' => 'Broadband',
    'risk' => 'Safe',
    'sunrise' => '--:--',
    'sunset' => '--:--'
];

$ipApi = "https://api.map.baidu.com/location/ip?ak={$SERVER_AK}&ip={$ip}&coor=bd09ll";

$ipRes = curlGet($ipApi);

if($ipRes){

    $json = json_decode($ipRes,true);

    if(isset($json['content'])){

        $detail = $json['content']['address_detail'] ?? [];

        $data['province'] = $detail['province'] ?? '';
        $data['city'] = $detail['city'] ?? '';
        $data['district'] = $detail['district'] ?? '';

        $data['lng'] = $json['content']['point']['x'] ?? '0';
        $data['lat'] = $json['content']['point']['y'] ?? '0';

        $data['operator'] = $json['content']['operators'] ?? 'Unknown';

        $geoApi = "https://api.map.baidu.com/reverse_geocoding/v3/?ak={$SERVER_AK}&output=json&coordtype=bd09ll&location=".$data['lat'].",".$data['lng'];

        $geoRes = curlGet($geoApi);

        if($geoRes){

            $geo = json_decode($geoRes,true);

            if(isset($geo['result']['addressComponent'])){

                $comp = $geo['result']['addressComponent'];

                $country = $comp['country'] ?? '';

                if(empty($country)){
                    $country = !empty($data['province']) ? '中国' : 'Unknown';
                }

                $data['country'] = $country;

                $data['address'] =
                    trim(
                        $country.' '.
                        $data['province'].' '.
                        $data['city'].' '.
                        $data['district']
                    );
            }
        }

        $tzApi = "https://api.map.baidu.com/timezone/v1?location=".$data['lat'].",".$data['lng']."&coord_type=wgs84ll&timestamp=".time()."&ak=".$SERVER_AK;

        $tzRes = curlGet($tzApi);

        if($tzRes){

            $tz = json_decode($tzRes,true);

            if(isset($tz['time_zone'])){

                $data['timezone'] = $tz['time_zone']['id'] ?? 'UTC';

                $offset = $tz['time_zone']['raw_offset'] ?? 0;

                $hours = floor($offset / 3600);

                $data['offset'] = ($hours >= 0 ? '+' : '').$hours.':00';
            }
        }

        $sunApi = "https://api.sunrise-sunset.org/json?lat=".$data['lat']."&lng=".$data['lng']."&formatted=0";

        $sunRes = curlGet($sunApi);

        if($sunRes){

            $sun = json_decode($sunRes,true);

            if(isset($sun['results'])){

                $data['sunrise'] = date('H:i',strtotime($sun['results']['sunrise']));
                $data['sunset'] = date('H:i',strtotime($sun['results']['sunset']));
            }
        }

        if(stripos($data['operator'],'mobile') !== false || strpos($data['operator'],'移动') !== false){
            $data['network'] = 'China Mobile';
        }elseif(stripos($data['operator'],'telecom') !== false || strpos($data['operator'],'电信') !== false){
            $data['network'] = 'China Telecom';
        }elseif(stripos($data['operator'],'unicom') !== false || strpos($data['operator'],'联通') !== false){
            $data['network'] = 'China Unicom';
        }
    }
}

$isCN = strpos($data['country'],'中国') !== false;

$lang = $isCN ? 'zh' : 'en';

$i18n = [

'zh' => [

'title'=>'青禾IP查询',
'sub'=>'青禾一站式IP查询',
'beijing'=>'北京时间',
'local'=>'本地时间',
'ip'=>'真实IP',
'copy'=>'复制',
'country'=>'国家',
'province'=>'省份',
'city'=>'城市',
'district'=>'区县',
'coords'=>'经纬度',
'operator'=>'运营商',
'network'=>'网络类型',
'timezone'=>'时区偏移',
'sunrise'=>'日出',
'sunset'=>'日落',
'risk'=>'IP风险',
'map'=>'全球地图',
'ai'=>'AI助手',
'send'=>'发送',
'clear'=>'清空',
'copychat'=>'复制记录',
'placeholder'=>'输入问题...',
'theme'=>'主题切换',
'share'=>'分享位置',
'generate'=>'生成分享链接',
'food'=>'附近美食',
'hotel'=>'附近酒店',
'spot'=>'附近景点',
'drive'=>'驾车路线',
'walk'=>'步行路线',
'bus'=>'公交路线',
'search'=>'搜索地点',
'search_place_holder'=>'请输入地址搜索',
'search_result'=>'搜索结果',
'search_lng'=>'经度',
'search_lat'=>'纬度',
'search_addr'=>'详细地址',
'search_name'=>'标准名称',
'safe'=>'安全'

],

'en' => [

'title'=>'QingHe IP Query',
'sub'=>'QingHe One-Stop IP System',
'beijing'=>'Beijing Time',
'local'=>'Local Time',
'ip'=>'Real IP',
'copy'=>'Copy',
'country'=>'Country',
'province'=>'Province',
'city'=>'City',
'district'=>'District',
'coords'=>'Coordinates',
'operator'=>'Operator',
'network'=>'Network',
'timezone'=>'Timezone',
'sunrise'=>'Sunrise',
'sunset'=>'Sunset',
'risk'=>'IP Risk',
'map'=>'Global Map',
'ai'=>'AI Assistant',
'send'=>'Send',
'clear'=>'Clear',
'copychat'=>'Copy Chat',
'placeholder'=>'Ask something...',
'theme'=>'Theme',
'share'=>'Share',
'generate'=>'Generate Link',
'food'=>'Food',
'hotel'=>'Hotels',
'spot'=>'Attractions',
'drive'=>'Driving',
'walk'=>'Walking',
'bus'=>'Transit',
'search'=>'Search',
'search_place_holder'=>'Search location...',
'search_result'=>'Search Result',
'search_lng'=>'Longitude',
'search_lat'=>'Latitude',
'search_addr'=>'Address',
'search_name'=>'Name',
'safe'=>'Safe'

]

];

$t = $i18n[$lang];

if(isset($_GET['ai'])){

    header('Content-Type:application/json;charset=utf-8');

    $message = trim($_POST['message'] ?? '');

    if(empty($_SESSION['chat'])){
        $_SESSION['chat'] = [];
    }

    $_SESSION['chat'][] = [
        'role' => 'user',
        'content' => $message
    ];

    $system = $lang == 'zh'
    ?
    "你是青禾一站式IP查询智能助手，回答简洁自然，严禁使用星号*、特殊符号修饰、禁止加粗、禁止格式标记，只用纯正常文字回答。用户 IP：{$data['ip']}，国家：{$data['country']}，城市：{$data['city']}，经纬度：{$data['lng']},{$data['lat']}。"
    :
    "You are QingHe IP assistant. Answer in plain text only, do not use asterisk *, no special symbols, no bold, no markdown format. User IP: {$data['ip']}, Country: {$data['country']}, City: {$data['city']}, Coordinates: {$data['lng']},{$data['lat']}.";

    $messages = [
        [
            'role' => 'system',
            'content' => $system
        ]
    ];

    foreach($_SESSION['chat'] as $m){
        $messages[] = $m;
    }

    $payload = [
        'model' => 'deepseek-chat',
        'messages' => $messages,
        'temperature' => 0.7
    ];

    $res = curlPost(
        "https://api.deepseek.com/chat/completions",
        $payload,
        [
            "Authorization: Bearer ".$DEEPSEEK_KEY
        ]
    );

    $json = json_decode($res,true);

    $reply = $json['choices'][0]['message']['content'] ?? 'AI Error';

    $_SESSION['chat'][] = [
        'role' => 'assistant',
        'content' => $reply
    ];

    echo json_encode([
        'reply' => $reply
    ],JSON_UNESCAPED_UNICODE);

    exit;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,viewport-fit=cover">

<title><?php echo $t['title']; ?></title>

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
}

:root{
--bg:#05060a;
--text:#fff;
--sub:#9ba3b3;
--glass:rgba(255,255,255,.08);
--glass2:rgba(255,255,255,.05);
--border:rgba(255,255,255,.12);
--radius:32px;
}

.light{
--bg:#eef2f8;
--text:#111;
--sub:#4d5565;
--glass:rgba(255,255,255,.48);
--glass2:rgba(255,255,255,.38);
--border:rgba(255,255,255,.32);
}

html,body{
width:100%;
min-height:100%;
background:var(--bg);
color:var(--text);
font-family:-apple-system,BlinkMacSystemFont,"SF Pro Display","PingFang SC","Helvetica Neue",sans-serif;
overflow-x:hidden;
transition:.35s;
}

body::before{
content:"";
position:fixed;
inset:0;
background:
linear-gradient(rgba(5,8,15,.58),rgba(5,8,15,.68)),
url('https://cdn.phototourl.com/free/2026-05-16-ebeb9a87-b8a9-4826-a6a6-9b530a53243d.png') center center / cover no-repeat fixed;
z-index:-3;
transform:scale(1.02);
}

body::after{
content:"";
position:fixed;
inset:0;
backdrop-filter:blur(8px);
-webkit-backdrop-filter:blur(8px);
z-index:-2;
}

.glass{
background:var(--glass);
border:1px solid var(--border);
backdrop-filter:blur(28px);
-webkit-backdrop-filter:blur(28px);
border-radius:var(--radius);
box-shadow:
0 10px 50px rgba(0,0,0,.35),
inset 0 1px 0 rgba(255,255,255,.06),
0 0 40px rgba(255,255,255,.03);
}

.topbar{
width:min(94%,1450px);
margin:30px auto;
position:relative;
z-index:9999;
}

.top-inner{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(260px,1fr));
gap:24px;
padding:0;
background:transparent;
border:none;
backdrop-filter:none;
}

.top-inner .item{
padding:34px;
transition:.35s;
background:var(--glass);
border:1px solid var(--border);
backdrop-filter:blur(28px);
-webkit-backdrop-filter:blur(28px);
border-radius:var(--radius);
box-shadow:
0 10px 50px rgba(0,0,0,.35),
inset 0 1px 0 rgba(255,255,255,.06),
0 0 40px rgba(255,255,255,.03);
display:flex;
flex-direction:column;
gap:6px;
min-width:170px;
}

.top-inner .item:hover{
transform:translateY(-8px);
box-shadow:
0 15px 60px rgba(0,0,0,.4),
0 0 50px rgba(255,255,255,.05);
}

.label{
font-size:12px;
letter-spacing:1px;
text-transform:uppercase;
color:var(--sub);
}

.value{
font-size:15px;
font-weight:600;
line-height:1.6;
word-break:break-word;
}

main{
padding-top:20px;
padding-bottom:80px;
}

.hero{
width:min(94%,1450px);
margin:auto;
}

.hero-card{
padding:110px 70px;
overflow:hidden;
position:relative;
}

.hero-card::before{
content:"";
position:absolute;
width:520px;
height:520px;
background:radial-gradient(circle,#ffffff15,transparent 70%);
top:-240px;
right:-180px;
animation:float 8s ease-in-out infinite;
}

@keyframes float{
0%,100%{transform:translateY(0)}
50%{transform:translateY(26px)}
}

.hero h1{
font-size:clamp(52px,8vw,120px);
line-height:1;
letter-spacing:-6px;
margin-bottom:26px;
background:linear-gradient(to bottom,#fff,#98a3b5);
-webkit-background-clip:text;
-webkit-text-fill-color:transparent;
}

.hero p{
max-width:900px;
font-size:20px;
line-height:1.9;
color:#d2d8e3;
}

.grid{
width:min(94%,1450px);
margin:30px auto;
display:grid;
grid-template-columns:repeat(auto-fit,minmax(260px,1fr));
gap:24px;
}

.card{
padding:34px;
transition:.35s;
}

.card:hover{
transform:translateY(-8px);
box-shadow:
0 15px 60px rgba(0,0,0,.4),
0 0 50px rgba(255,255,255,.05);
}

.card-title{
font-size:12px;
color:var(--sub);
letter-spacing:1px;
text-transform:uppercase;
margin-bottom:15px;
}

.card-value{
font-size:28px;
font-weight:700;
line-height:1.5;
}

.btn{
border:none;
outline:none;
padding:12px 18px;
border-radius:16px;
cursor:pointer;
font-size:14px;
color:var(--text);
background:rgba(255,255,255,.08);
backdrop-filter:blur(20px);
transition:.25s;
}

.btn:hover{
transform:translateY(-2px);
background:rgba(255,255,255,.15);
}

/* 地图搜索栏样式 */
.map-search-row{
display:flex;
gap:12px;
margin-bottom:16px;
flex-wrap:wrap;
}
.map-search-input{
flex:1;
min-width:220px;
border:none;
outline:none;
padding:12px 16px;
border-radius:16px;
background:rgba(255,255,255,.08);
color:var(--text);
font-size:14px;
backdrop-filter:blur(20px);
}

/* 搜索结果卡片 */
.search-result-card{
margin-top:16px;
padding:20px;
}

.map-wrap,
.ai-wrap{
width:min(94%,1450px);
margin:30px auto;
}

.map-card,
.ai-card{
padding:20px;
}

.map-tools{
display:flex;
flex-wrap:wrap;
gap:12px;
margin-bottom:16px;
}

#map{
width:100%;
height:600px;
border-radius:26px;
overflow:hidden;
}

.ai-chat{
height:430px;
overflow:auto;
padding:20px;
border-radius:24px;
background:rgba(255,255,255,.03);
margin-bottom:16px;
}

.msg{
padding:16px;
border-radius:18px;
background:rgba(255,255,255,.06);
margin-bottom:14px;
line-height:1.8;
}

.input-row{
display:flex;
gap:14px;
}

.input{
flex:1;
border:none;
outline:none;
padding:16px 18px;
border-radius:20px;
background:rgba(255,255,255,.06);
color:var(--text);
font-size:15px;
}

.footer{
text-align:center;
padding:45px 20px 55px;
font-size:13px;
color:#c3cad5;
}

.copy-group{
margin-top:12px;
display:flex;
gap:10px;
}

@media(max-width:768px){

body::before{
background:
linear-gradient(rgba(5,8,15,.62),rgba(5,8,15,.72)),
url('https://cdn.phototourl.com/free/2026-05-16-ebeb9a87-b8a9-4826-a6a6-9b530a53243d.png') center center / cover no-repeat fixed;
}

main{
padding-top:20px;
}

.hero-card{
padding:65px 30px;
}

.hero p{
font-size:16px;
}

.card-value{
font-size:20px;
}

#map{
height:420px;
}

.input-row{
flex-direction:column;
}

}

</style>
</head>
<body>

<div class="topbar">
<div class="top-inner">

<div class="item">
<div class="label"><?php echo $t['beijing']; ?></div>
<div class="value" id="bjTime">--</div>
</div>

<div class="item">
<div class="label"><?php echo $t['local']; ?></div>
<div class="value" id="localTime">--</div>
</div>

<div class="item">
<div class="label"><?php echo $t['ip']; ?></div>
<div class="value">
<?php echo htmlspecialchars($data['ip']); ?>
<div class="copy-group">
<button class="btn" onclick="copyText('<?php echo $data['ip']; ?>')"><?php echo $t['copy']; ?></button>
</div>
</div>
</div>

<div class="item">
<div class="label"><?php echo $t['coords']; ?></div>
<div class="value">
<?php echo $data['lng']; ?> , <?php echo $data['lat']; ?>
<div class="copy-group">
<button class="btn" onclick="copyText('<?php echo $data['lng']; ?>,<?php echo $data['lat']; ?>')"><?php echo $t['copy']; ?></button>
</div>
</div>
</div>

</div>
</div>

<main>

<section class="hero">
<div class="glass hero-card">

<h1><?php echo $t['title']; ?></h1>

<p><?php echo $t['sub']; ?></p>

</div>
</section>

<section class="grid">

<?php

$cards = [

[$t['country'],$data['country']],
[$t['province'],$data['province']],
[$t['city'],$data['city']],
[$t['district'],$data['district']],
[$t['operator'],$data['operator']],
[$t['network'],$data['network']],
[$t['timezone'],$data['offset']],
[$t['sunrise'],$data['sunrise']],
[$t['sunset'],$data['sunset']],
[$t['risk'],$t['safe']]

];

foreach($cards as $c){

echo '
<div class="glass card">
<div class="card-title">'.$c[0].'</div>
<div class="card-value">'.$c[1].'</div>
</div>
';

}

?>

<div class="glass card">
<div class="card-title"><?php echo $t['theme']; ?></div>
<div class="card-value">
<button class="btn" onclick="toggleTheme()"><?php echo $t['theme']; ?></button>
</div>
</div>

<div class="glass card">
<div class="card-title"><?php echo $t['share']; ?></div>
<div class="card-value">
<button class="btn" onclick="shareLocation()"><?php echo $t['generate']; ?></button>
</div>
</div>

</section>

<section class="map-wrap">
<div class="glass map-card">

<!-- 地图双语言搜索框 -->
<div class="map-search-row">
<input type="text" id="mapSearchInput" class="map-search-input" placeholder="<?php echo $t['search_place_holder']; ?>">
<button class="btn" onclick="searchLocation()"><?php echo $t['search']; ?></button>
</div>

<div class="map-tools">

<button class="btn" onclick="switchTheme('midnight')">Midnight</button>
<button class="btn" onclick="switchTheme('dark')">Dark</button>
<button class="btn" onclick="switchTheme('light')">Light</button>

<button class="btn" onclick="nearby('<?php echo $lang=='zh'?'美食':'food'; ?>')"><?php echo $t['food']; ?></button>
<button class="btn" onclick="nearby('<?php echo $lang=='zh'?'酒店':'hotel'; ?>')"><?php echo $t['hotel']; ?></button>
<button class="btn" onclick="nearby('<?php echo $lang=='zh'?'景点':'attractions'; ?>')"><?php echo $t['spot']; ?></button>

<button class="btn" onclick="route('DRIVING')"><?php echo $t['drive']; ?></button>
<button class="btn" onclick="route('WALKING')"><?php echo $t['walk']; ?></button>
<button class="btn" onclick="route('TRANSIT')"><?php echo $t['bus']; ?></button>

</div>

<div id="map"></div>

<!-- 搜索结果显示卡片 -->
<div id="searchResultCard" class="glass search-result-card" style="display:none;">
<div class="card-title"><?php echo $t['search_result']; ?></div>
<div class="card-value" style="font-size:16px;line-height:1.8;">
<div><b><?php echo $t['ip']; ?>：</b><span id="resIp"><?php echo $data['ip']; ?></span></div>
<div><b><?php echo $t['search_lng']; ?>：</b><span id="resLng"></span></div>
<div><b><?php echo $t['search_lat']; ?>：</b><span id="resLat"></span></div>
<div><b><?php echo $t['search_addr']; ?>：</b><span id="resAddr"></span></div>
<div><b><?php echo $t['search_name']; ?>：</b><span id="resName"></span></div>
</div>
</div>

</div>
</section>

<section class="ai-wrap">
<div class="glass ai-card">

<div class="card-title"><?php echo $t['ai']; ?></div>

<div class="ai-chat" id="chat"></div>

<div class="input-row">

<input type="text" id="msg" class="input" placeholder="<?php echo $t['placeholder']; ?>">

<button class="btn" onclick="sendAI()"><?php echo $t['send']; ?></button>

<button class="btn" onclick="clearChat()"><?php echo $t['clear']; ?></button>

<button class="btn" onclick="copyChat()"><?php echo $t['copychat']; ?></button>

</div>

</div>
</section>

</main>

<footer class="footer">
© <?php echo date('Y'); ?> <?php echo $t['title']; ?>
</footer>

<script src="https://api.map.baidu.com/api?v=3.0&ak=<?php echo $BROWSER_AK; ?>"></script>

<script>

const TZ = <?php echo json_encode($data['timezone']); ?>;
const USER_IP = <?php echo json_encode($data['ip']); ?>;

function formatTime(date){

const y = date.getFullYear();
const m = String(date.getMonth()+1).padStart(2,'0');
const d = String(date.getDate()).padStart(2,'0');

const h = String(date.getHours()).padStart(2,'0');
const i = String(date.getMinutes()).padStart(2,'0');
const s = String(date.getSeconds()).padStart(2,'0');
const ms = String(date.getMilliseconds()).padStart(3,'0');

return `${y}-${m}-${d} ${h}:${i}:${s}.${ms}`;

}

function updateClock(){

const now = new Date();

const bj = new Date(
now.toLocaleString("en-US",{
timeZone:"Asia/Shanghai"
})
);

const local = new Date(
now.toLocaleString("en-US",{
timeZone:TZ
})
);

document.getElementById('bjTime').innerHTML = formatTime(bj);
document.getElementById('localTime').innerHTML = formatTime(local);

}

setInterval(updateClock,10);

updateClock();

function copyText(text){
navigator.clipboard.writeText(text);
}

function toggleTheme(){
document.body.classList.toggle('light');
}

const lng = <?php echo json_encode((float)$data['lng']); ?>;
const lat = <?php echo json_encode((float)$data['lat']); ?>;

const map = new BMap.Map("map");
const point = new BMap.Point(lng,lat);
map.centerAndZoom(point,12);
map.enableScrollWheelZoom(true);
map.addControl(new BMap.NavigationControl());
map.addControl(new BMap.ScaleControl());
map.setMapStyleV2({styleId:'midnight'});

const marker = new BMap.Marker(point);
map.addOverlay(marker);

// 地址搜索定位 + 显示结果
function searchLocation(){
    let val = document.getElementById("mapSearchInput").value.trim();
    if(!val) return;
    var myGeo = new BMap.Geocoder();
    myGeo.getPoint(val, function(pt){
        if(pt){
            map.centerAndZoom(pt,15);
            map.clearOverlays();
            var newMarker = new BMap.Marker(pt);
            map.addOverlay(newMarker);

            // 逆解析获取详细地址和标准名称
            myGeo.getLocation(pt, function(rs){
                let addComp = rs.addressComponents;
                let fullAddr = addComp.province + addComp.city + addComp.district + addComp.street + addComp.streetNumber;
                let poiName = rs.suggestions && rs.suggestions.length>0 ? rs.suggestions[0].name : val;

                // 填充结果卡片
                document.getElementById("resIp").innerText = USER_IP;
                document.getElementById("resLng").innerText = pt.lng;
                document.getElementById("resLat").innerText = pt.lat;
                document.getElementById("resAddr").innerText = fullAddr;
                document.getElementById("resName").innerText = poiName;

                // 显示卡片
                document.getElementById("searchResultCard").style.display = "block";
            });
        }
    });
}
// 回车也能搜索
document.getElementById("mapSearchInput").addEventListener("keydown",function(e){
    if(e.key === "Enter") searchLocation();
});

function switchTheme(style){
map.setMapStyleV2({styleId:style});
}

function nearby(keyword){
const local = new BMap.LocalSearch(map,{renderOptions:{map:map}});
local.searchNearby(keyword,point);
}

function route(type){
const dest = prompt("Destination");
if(!dest) return;
let searcher;
if(type === 'DRIVING'){
searcher = new BMap.DrivingRoute(map,{renderOptions:{map:map}});
}
if(type === 'WALKING'){
searcher = new BMap.WalkingRoute(map,{renderOptions:{map:map}});
}
if(type === 'TRANSIT'){
searcher = new BMap.TransitRoute(map,{renderOptions:{map:map}});
}
searcher.search(point,dest);
}

async function sendAI(){
const msg = document.getElementById('msg').value.trim();
if(!msg) return;
appendMsg('You',msg);
document.getElementById('msg').value = '';
const form = new FormData();
form.append('message',msg);
const res = await fetch('?ai=1',{method:'POST',body:form});
const json = await res.json();
appendMsg('AI',json.reply);
}

function appendMsg(role,text){
const chat = document.getElementById('chat');
const div = document.createElement('div');
div.className = 'msg';
div.innerHTML = `<b>${role}</b><br>${text}`;
chat.appendChild(div);
chat.scrollTop = chat.scrollHeight;
}

function clearChat(){
document.getElementById('chat').innerHTML = '';
}

function copyChat(){
navigator.clipboard.writeText(document.getElementById('chat').innerText);
}

function shareLocation(){
const url = location.origin + location.pathname + '?lng=' + lng + '&lat=' + lat;
navigator.clipboard.writeText(url);
alert(url);
}

</script>

</body>
</html>
