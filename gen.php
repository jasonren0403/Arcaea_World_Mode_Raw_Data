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
);
// previous charas not filled
$charater = [
  0,0,0,0,0,0,0,0,0,0,'Ilith',0,0,0,0,0,
  'Hikari (Summer)',
  'Tairitsu (Summer)',
  'Tairitsu & Trin',
  'Ayu',
  'Eto & Luna',
  'Yume',
  'Hikari & Seine'
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
{| style="width: 500px;" class="article-table" cellspacing="1" cellpadding="1" border="0"
|-
! scope="col"|Tile !! scope="col"|Step<br />(remaining to next reward) !! scope="col"|Special tile !! scope="col"|Reward
');
$total = [0,0,0,0];
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
    $total[0] += $step;
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
    $reward = '-';
    if (isset($tile['items'])) {
      switch($tile['items'][0]['type']) {
        case 'fragment': $reward = getFrag($tile['items'][0]) .' fragments'; $total[1] += getFrag($tile['items'][0]); break;
        case 'character': $reward = '[['. $charater[$tile['items'][0]['id']].']]'; $total[2]++; break;
        case 'world_song': $reward = '[['. $songs[$tile['items'][0]['id']].']]'; $total[3]++; break;
      }
    }

    fwrite($f, "|-
| ${i} || ${step} (${remaining}) || ${restriction} || ${reward}
");
  }
  $total_reward = [$total[1].' fragments'];
  if ($total[2]) $total_reward[] = $total[2].' character';
  if ($total[3]) $total_reward[] = $total[3].' song'.($total[3]>1?'s':'');
  fwrite($f, '|-
| Total || \'\'\''.$total[0].'\'\'\' || \'\'\'-\'\'\' || \'\'\''.implode(', ', $total_reward).'\'\'\'
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