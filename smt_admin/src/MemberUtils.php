<?php

/**
 * @file
 * Contains \Drupal\smt_admin\MemberUtils.
 */

namespace Drupal\smt_admin;

use Drupal\Core\Database\Database;

/**
 * member util methods
 */
class MemberUtils {

    // does the user have a member record
    public static function is_member($uid) {

        $db = Database::getConnection();

        $sql = "SELECT m.ID FROM members m WHERE m.ID = ?";
        $member = $db->query($sql, array($uid))->fetch();

        return !empty($member);
    }

    // fetch member record
    public static function member($uid) {

        $db = Database::getConnection();

        $sql = "SELECT m.*, u.mail as email
            FROM members m
            INNER JOIN users_field_data u ON u.uid = m.ID
            WHERE m.ID = ?";

        return $db->query($sql, array($uid))->fetch();
    }


    // does the user exist
    public static function is_user($uid) {

        $db = Database::getConnection();

        $sql = "SELECT u.uid FROM users_field_data m WHERE u.uid = ?";
        $user = $db->query($sql, array($uid))->fetch();

        return !empty($user);

    }

    // fetch user record
    public static function user($uid) {

        $db = Database::getConnection();

        $sql = "SELECT * FROM users_field_data m WHERE m.ID = ?";

        return $db->query($sql, array($uid))->fetch();
    }

    // fetch smtprofile record
    public static function smtprofile($uid) {

        $db = Database::getConnection();

        $sql = "SELECT p.*, u.mail as email
            FROM smtprofiles p
            INNER JOIN users_field_data u ON u.uid = p.ID
            WHERE p.ID = ?";

        return $db->query($sql, array($uid))->fetch();
    }

}
