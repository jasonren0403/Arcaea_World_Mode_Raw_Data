<?php
chdir(__DIR__);

$f= fopen('out.txt', 'w');

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
);
// previous charas not filled
$charater = [
  0,1,2,3,4,5,'Tairitsu (Axium Crisis)','Tairitsu (Grievous Lady)',8,'Hikari & Fisica','Ilith','Eto','Luna',12,'Hikari (Zero)','Hikari (Fracture)',
  'Hikari (Summer)',
  'Tairitsu (Summer)',
  'Tairitsu & Trin',
  'Ayu',
  'Eto & Luna',
  'Yume',
  'Hikari & Seine',
  'Saya'
];
// two files from unpacked game
$songlist = json_decode(file_get_contents('songlist'), true);
$packlist = json_decode(file_get_contents('packlist'), true);
$songs = []; $packs = [];
foreach ($songlist['songs'] as &$song) {
  $songs[$song['id']] = $song['title_localized']['en'];
}
foreach ($packlist['packs'] as &$pack) {
  $packs[$pack['id']] = $pack['name_localized']['en'];
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
  $total = [
    'step' => 0,
    'frag' => 0,
    'char' => 0,
    'song' => 0,
    'ether' => 0,
    'hollow' => 0,
    'desolate' => 0,
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
    if (isset($tile['step_type'])) {
      foreach ($tile['step_type'] as $st) {
        switch ($st) {
          case 'plusstamina': {
            $restriction[] = '+'.$tile['plus_stamina_value'].' stamina';
            break;
          }
          case 'randomsong': {
            $restriction[] = 'Random song';
            break;
          }
          case 'speedlimit': {
            $restriction[] = 'Fixed Speed ≤ '.($tile['speed_limit_value']/10);
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
        case 'song_id': $restriction[] = 'Restrict [['.$songs[$tile['restrict_id']].']]'; break;
        case 'pack_id': $restriction[] = 'Restrict [['.$packs[$tile['restrict_id']].']]'; break;
      }
    }
    if (empty($restriction)) $restriction[] = '-';
    $restriction = implode("<br />", $restriction);
    $reward = [];
    if (isset($tile['items'])) {
      if (isset($tile['items'][1])) {
        print_r($tile['items']);exit;
      }
      switch($tile['items'][0]['type']) {
        case 'fragment': $reward[] = getFrag($tile['items'][0]) .' fragments'; $total['frag'] += getFrag($tile['items'][0]); break;
        case 'character': $reward[] = '[['. $charater[$tile['items'][0]['id']].']]'; $total['song']++; break;
        case 'world_song': $reward[] = '[['. $songs[$tile['items'][0]['id']].']]'; $total['char']++; break;
        case 'core': {
          switch ($tile['items'][0]['id']) {
            case 'core_generic': { $reward[] = 'Ether Drop &times; '.$tile['items'][0]['amount']; $total['ether']+=$tile['items'][0]['amount']; break; }
            case 'core_hollow': { $reward[] = 'Hollow Core &times; '.$tile['items'][0]['amount']; $total['hollow']+=$tile['items'][0]['amount']; break; }
            case 'core_desolate': { $reward[] = 'Desolate Core &times; '.$tile['items'][0]['amount']; $total['desolate']+=$tile['items'][0]['amount']; break; }
            default: {print_r($tile['items']);exit;}
          }
          break;
        }
        default: {print_r($tile['items']);exit;}
      }
    }
    $reward = implode('<br />', $reward) ?: '-';

    fwrite($f, "|-
| ${i} || ${step} (${remaining}) || ${restriction} || ${reward}
");
  }
  $total_reward = [$total['frag'].' fragments'];
  if ($total['song']) $total_reward[] = $total['song'].' character';
  if ($total['char']) $total_reward[] = $total['char'].' song'.($total['char']>1?'s':'');
  if ($total['ether']) $total_reward[] = $total['ether'].' Ether Drop'.($total['ether']>1?'s':'');
  if ($total['hollow']) $total_reward[] = $total['hollow'].' Hollow Core'.($total['hollow']>1?'s':'');
  if ($total['desolate']) $total_reward[] = $total['desolate'].' Desolate Core'.($total['desolate']>1?'s':'');
  fwrite($f, '|-
| Total || \'\'\''.$total['step'].'\'\'\' || \'\'\'-\'\'\' || \'\'\''.implode(', ', $total_reward).'\'\'\'
|}

-------

');
}

function getFrag($i) {
  if (isset($i['id'])) {
    return $i['id'];
  }
  if (isset($i['amount'])) {
    return $i['amount'];
  }
  throw new Exception('no key');
}