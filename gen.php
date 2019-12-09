<?php
chdir(__DIR__);

$f= fopen('out.txt', 'w');
$cn = fopen('out_cn.txt', 'w');

$map = array(
  '0-0'=>'Fragments',
  '0-L'=>'Lanota',
  '0-E'=>'Event',
  '0-LE'=>'Event',
  '0-TS'=>'Tone Sphere',
  '0-GC'=>'Groove Coaster',
  '1-1'=>'Light I',
  '1-2'=>'Conflict I',
  '1-3'=>'Light II',
  '1-4'=>'Conflict II',
  '1-5'=>'Eternal',
  '1-6'=>'Vicious',
  '1-7'=>'Grievous',
  '2-1'=>'Syro/Blaster',
  '2-2'=>'Binary I',
  '2-3'=>'Binary II',
  '2-4'=>'γuarδina',
  '3-1'=>'Conflict III',
  '3-2'=>'Light III',
  '3-3'=>'Luminous',
  '3-4'=>'Fracture',
  '3-5'=>'Conflict IV',
  '3-6'=>'Solitary Dream',
  '4-1'=>'Absolute Reason',
  '4-2'=>'Light IV',
  '4-3'=>'Conflict V',
  '4-4'=>'CHUNITHM',
  '4-5'=>'Festival',
  '4-6'=>'Glory:Road',
  '4-7'=>'Diode/FREEF4LL',
);
// previous charas not filled
$charater = [
  0,1,2,3,4,5,'Tairitsu (Axium)','Tairitsu (Grievous Lady)',8,'Hikari & Fisica','Ilith','Eto','Luna',12,'Hikari (Zero)','Hikari (Fracture)',
  'Hikari (Summer)',
  'Tairitsu (Summer)',
  'Tairitsu & Trin',
  'Ayu',
  'Eto & Luna',
  'Yume',
  'Hikari & Seine',
  'Saya', // 23 10
  'Tairitsu & Chuni Penguin', // 24
  'Chuni Penguin', // 25
  'Haruna', // 26
  'Nono', // 27
  'MTA-XXX', // 28
  'MDA-21', // 29
  'Kanae', // 30
  '','',
  'Sia', // 33
];
$charater_cn = [
  0,1,2,3,4,5,'对立（Axium）','对立（Grievous Lady）',8,'光&菲希卡','依莉丝','爱托','露娜',12,'光（Zero）','光（Fracture）',
  '光（Summer）',
  '对立（Summer）',
  '对立 & 托凛',
  '彩梦',
  '爱托 & 露娜 -冬-',
  '梦',
  '光 & 晴音',
  '咲弥',
  '对立 & 中二企鹅', // 24
  '中二企鹅', // 25
  '榛名', // 26
  '诺诺', // 27
  'MTA-XXX', // 28
  'MDA-21', // 29
  '群愿', // 30
  '','',
  '兮娅', // 33
];
$core=0;
// two files from unpacked game
$songlist = json_decode(file_get_contents('songlist'), true);
$packlist = json_decode(file_get_contents('packlist'), true);
$songs = []; $packs = []; $packs_cn = [];
foreach ($songlist['songs'] as &$song) {
  $songs[$song['id']] = str_replace(['[',']'],['【','】'],$song['title_localized']['en']);
}
foreach ($packlist['packs'] as &$pack) {
  $packs[$pack['id']] = $pack['name_localized']['en'];
  $packs_cn[$pack['id']] = $pack['name_localized']['en'];
  if (isset($pack['custom_banner']) && $pack['id'] != 'base') $packs_cn[$pack['id']] .= ' Collaboration';
}

chdir(__DIR__);
chdir('data');
foreach (glob('*-*.json') as $file) {
  $numid = strtoupper(explode('_',$file)[0]);
  $file = json_decode(file_get_contents($file), true);
  $id = $file['value']['maps'][0]['map_id'];
  $tiles = $file['value']['maps'][0]['steps'];
  fwrite($f, '=='.$numid.' '.$map[$numid].'==
'.$map[$numid].' (internal named "'.$id.'")
{| style="width: 500px;" class="article-table mw-collapsible" cellspacing="1" cellpadding="1" border="0"
|-
! scope="col"|Tile !! scope="col"|Step<br />(remaining to next reward) !! scope="col"|Special tile !! scope="col"|Reward
');
  fwrite($cn, '= '.$numid.' '.$id.' =

{| class="wikitable mw-collapsible mw-collapsed" border="1" cellspacing="0" cellpadding="5" style="text-align:center"
|-
! scope="col"|级数 !! scope="col"|步数<br />（到下一奖励的步数） !! scope="col"|特殊阶梯 !! scope="col"|奖励
');
  $total = [
    'step' => 0,
    'frag' => 0,
    'char' => 0,
    'song' => 0,
    'ether' => 0,
    'hollow' => 0,
    'desolate' => 0,
    'chunithm' => 0,
    'crimson' => 0,
    'item' => []
  ];
  $i=0;
  $remain = [0];
  $remainIndex = 0;
  foreach ($tiles as $tile) {
    if (isset($tile['items']) && $tile['items'][0]['type'] != 'fragment') {
      $remainIndex++;
      $remain[$remainIndex] = 0;
    }
    $remain[$remainIndex] += $tile['capture'];
  }
  $remaining = $remain[0];
  $remainIndex = 0;
  foreach ($tiles as $tile) {
    $i++;
    $step = $tile['capture'];
    $total['step'] += $step;
    if ($remaining == 0 && $step != 0) {
      $remainIndex++;
      $remaining = $remain[$remainIndex];
    }
    $remaining -= $step;
    $restriction = [];
    $restriction_cn = [];
    if (isset($tile['step_type'])) {
      foreach ($tile['step_type'] as $st) {
        switch ($st) {
          case 'plusstamina': {
            $restriction[] = '+'.$tile['plus_stamina_value'].' stamina';
            $restriction_cn[] = '+'.$tile['plus_stamina_value'].' 体力';
            break;
          }
          case 'randomsong': {
            $restriction[] = 'Random song';
            $restriction_cn[] = '随机歌曲';
            break;
          }
          case 'speedlimit': {
            $restriction[] = 'Fixed Speed ≤ '.($tile['speed_limit_value']/10);
            $restriction_cn[] = '限速 ≤ '.($tile['speed_limit_value']/10);
            break;
          }
          default: {
            print_r($tile);
            exit;
          }
        }
      }
    }
    if (isset($tile['restrict_type'])) {
      switch ($tile['restrict_type']) {
        case 'song_id': {$restriction[] = 'Restrict [['.$songs[$tile['restrict_id']].']]'; $restriction_cn[] = '限制 [['.$songs[$tile['restrict_id']].']]'; break;}
        case 'pack_id': {$restriction[] = 'Restrict [['.$packs[$tile['restrict_id']].']]'; $restriction_cn[] = '限制 [['.$packs_cn[$tile['restrict_id']].']]'; break;}
      }
    }
    if (empty($restriction)) $restriction[] = '-';
    if (empty($restriction_cn)) $restriction_cn[] = '-';
    $restriction = implode("<br />", $restriction);
    $restriction_cn = implode("<br />", $restriction_cn);
    $reward = [];
    $reward_cn = [];
    if (isset($tile['items'])) {
      if (isset($tile['items'][1])) {
        print_r($tile['items']);exit;
      }
      switch($tile['items'][0]['type']) {
        case 'fragment': {
          $reward[] = getFrag($tile['items'][0]) .' fragments';
          $reward_cn[] = getFrag($tile['items'][0]) .' 残片';
          $total['frag'] += getFrag($tile['items'][0]); break;
        }
        case 'character': {
          $reward[] = '[['. $charater[$tile['items'][0]['id']].']]';
          $total['item'][] = $reward_cn[] = '[[搭档#|'. $charater_cn[$tile['items'][0]['id']].']]';
          $total['char']++; break;
        }
        case 'world_song': {
          $reward[] = '[['. $songs[$tile['items'][0]['id']].']]';
          $total['item'][] = $reward_cn[] = '[['. $songs[$tile['items'][0]['id']].']]';
          $total['song']++; break;
        }
        case 'core': {
          switch ($tile['items'][0]['id']) {
            case 'core_generic': {
              $core+=$tile['items'][0]['amount'];
              $reward[] = 'Ether Drop &times; '.$tile['items'][0]['amount'];
              $reward_cn[] = '以太之滴 &times; '.$tile['items'][0]['amount'];
              $total['ether']+=$tile['items'][0]['amount']; break;
            }
            case 'core_hollow': {
              $reward[] = 'Hollow Core &times; '.$tile['items'][0]['amount'];
              $reward_cn[] = '中空核心 &times; '.$tile['items'][0]['amount'];
              $total['hollow']+=$tile['items'][0]['amount']; break;
            }
            case 'core_desolate': {
              $reward[] = 'Desolate Core &times; '.$tile['items'][0]['amount'];
              $reward_cn[] = '荒芜核心 &times; '.$tile['items'][0]['amount'];
              $total['desolate']+=$tile['items'][0]['amount']; break;
            }
            case 'core_chunithm': {
              $reward[] = 'CHUNITHM Core &times; '.$tile['items'][0]['amount'];
              $reward_cn[] = 'CHUNITHM 核心 &times; '.$tile['items'][0]['amount'];
              $total['chunithm']+=$tile['items'][0]['amount']; break;
            }
            case 'core_crimson': {
              $reward[] = 'Crimson Core &times; '.$tile['items'][0]['amount'];
              $reward_cn[] = '深红核心 &times; '.$tile['items'][0]['amount'];
              $total['crimson']+=$tile['items'][0]['amount']; break;
            }
            default: {print_r($tile['items']);exit;}
          }
          break;
        }
        default: {print_r($tile['items']);exit;}
      }
    }
    $reward = implode('<br />', $reward) ?: '-';
    $reward_cn = implode('<br />', $reward_cn) ?: '-';

    fwrite($f, "|-
| ${i} || ${step} (${remaining}) || ${restriction} || ${reward}
");
  fwrite($cn, "|-
| ${i} || ${step} (${remaining}) || ${restriction_cn} || ${reward_cn}
");
  }
  $total_reward = [$total['frag'].' fragments'];
  if ($total['char']) $total_reward[] = $total['char'].' character';
  if ($total['song']) $total_reward[] = $total['song'].' song'.($total['song']>1?'s':'');
  if ($total['ether']) $total_reward[] = $total['ether'].' Ether Drop'.($total['ether']>1?'s':'');
  if ($total['hollow']) $total_reward[] = $total['hollow'].' Hollow Core'.($total['hollow']>1?'s':'');
  if ($total['desolate']) $total_reward[] = $total['desolate'].' Desolate Core'.($total['desolate']>1?'s':'');
  if ($total['chunithm']) $total_reward[] = $total['chunithm'].' CHUNITHM Core'.($total['chunithm']>1?'s':'');
  if ($total['crimson']) $total_reward[] = $total['crimson'].' Crimson Core'.($total['crimson']>1?'s':'');
  $total_reward_cn = [$total['frag'].' 残片'];
  if (count($total['item'])) $total_reward_cn[] = implode('<br />', $total['item']);
  if ($total['ether']) $total_reward_cn[] = $total['ether'].' 以太之滴';
  if ($total['hollow']) $total_reward_cn[] = $total['hollow'].' 中空核心';
  if ($total['desolate']) $total_reward_cn[] = $total['desolate'].' 荒芜核心';
  if ($total['chunithm']) $total_reward_cn[] = $total['chunithm'].' CHUNITHM 核心';
  if ($total['crimson']) $total_reward_cn[] = $total['crimson'].' 深红核心';
  fwrite($f, '|-
| Total || \'\'\''.$total['step'].'\'\'\' || \'\'\'-\'\'\' || \'\'\''.implode(', ', $total_reward).'\'\'\'
|}

-------

');
  fwrite($cn, '|-
| 总计 || \'\'\''.$total['step'].'\'\'\' || \'\'\'-\'\'\' || \'\'\''.implode('<br />', $total_reward_cn).'\'\'\'
|}

-------

');
  echo "$numid $id $core\n";
}
var_dump($core);
function getFrag($i) {
  if (isset($i['id'])) {
    return $i['id'];
  }
  if (isset($i['amount'])) {
    return $i['amount'];
  }
  throw new Exception('no key');
}