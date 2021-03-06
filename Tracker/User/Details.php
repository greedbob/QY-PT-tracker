<?php namespace Tracker\User;

use Tracker\SQL;

// TODO: set user id and cache clear flag when construct.

class Details extends SQL {
  private static $fields = [
    'simple' => 'id,username,email,class,avatar,seedbonus,donor,noad,parked,leechwarn,status',
  ];

  private static $cachePrefix = 'user_detail_';

  public function getUserInfo($id, $type, $clearCache = false) {
    global $Cache;

    $uid = $this->esc($id);
    $cacheKey = static::$cachePrefix . $id . '_' . $type;

    if ($clearCache || !$row = $Cache->get_value($cacheKey)) {
      $res = $this->sql->query("SELECT " . static::$fields[$type] . " FROM users WHERE id='$uid'")
        or $this->throwSQLError();

      $row = $res->fetch_assoc();

      if ($row) $Cache->cache_value($cacheKey, $row, 1800);
    }

    return $row;
  }

  public function getTrackerInfo($id, $clearCache = false) {
    global $Cache;

    $uid = $this->esc($id);
    $cacheKey = static::$cachePrefix . $id . '_tracker';

    if (!$clearCache || !$info = $Cache->get_value($cacheKey)) {
      $info = [];

      $res = $this->sql->query("SELECT bonus,up,dl,seed FROM tracker_bonus WHERE id='$uid'")
        or $this->throwSQLError();
      $row = $res->fetch_assoc();
      $up = ($row['up'] - $row['dl']) / 1024 / 1024 / 1024;
      $info['up'] = round($up, 2);
      $info['hp'] = $row['bonus'];
      $info['time'] = $row['seed'];

      $res = $this->sql->query("SELECT COUNT(*),SUM(seeder) FROM tracker_peers WHERE userid='$uid'")
        or $this->throwSQLError();
      $row = $res->fetch_row();
      $info['seed'] = $row[1] ?: 0;
      $info['leech'] = $row[0] - $info['seed'];

      $Cache->cache_value($cacheKey, $info, 1200);
    }

    return $info;
  }

  public function getUnreadCount($id) {
    global $Cache;

    // TODO:
    // compatible old version
    $count = $Cache->get_value("user_${id}_unread_message_count");
    return $count ? intval($count) : 0;
  }

  public function getSigninInfo($id) {
    $res = $this->sql->query("SELECT (TO_DAYS(CURRENT_TIMESTAMP) - TO_DAYS(last_signin)) as l, total_days as t FROM signin_bonus WHERE id='$id'")
      or $this->throwSQLError();
    $row = $res->fetch_assoc();
    return $row ?: [ 'l' => -1, 't' => 0 ];
  }

}
