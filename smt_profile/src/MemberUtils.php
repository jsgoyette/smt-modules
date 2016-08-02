<?php

/**
 * @file
 * Contains \Drupal\smt_profile\MemberUtils.
 */

namespace Drupal\smt_profile;

use Drupal\Core\Database\Database;

/**
 * Standard member util methods
 */
class MemberUtils {

    // does the user have a member record
    public static function is_member() {

        $db = Database::getConnection();
        $uid = \Drupal::currentUser()->id();

        if ($uid == 0) return false;
        // if ($uid == 1) $uid = 2;

        $sql = "SELECT * FROM members m WHERE m.ID = :id";
        return $db->query($sql, array(':id' => $uid))->fetch();
    }

    // fetch member record
    public static function member() {

        $db = Database::getConnection();
        $uid = \Drupal::currentUser()->id();

        if ($uid == 0) return false;
        // if ($uid == 1) $uid = 2;

        $sql = "SELECT * FROM members m WHERE m.ID = :id";
        return $db->query($sql, array(':id' => $uid))->fetch();
    }

    // does the user have a non-expired member record
    public static function is_active_member() {

        $db = Database::getConnection();
        $uid = \Drupal::currentUser()->id();

        if ($uid == 0) return false;
        // if ($uid == 1) $uid = 2;

        $sql = "SELECT * FROM members m WHERE m.ID = :id";
        $data = $db->query($sql, array(':id' => $uid))->fetch();

        if (!$data) return false;

        if ($data->Date_renewed == '0000-00-00') {
            return false;
        }

        $today = strtotime('now');
        $expiration_date = strtotime($data->Date_renewed);

        if ($expiration_date < $today) {
            return false;
        }

        return $data;
    }

    // ensure that user has profile
    public static function smtprofile_find_or_create_profile() {

        $db = Database::getConnection();
        $uid = \Drupal::currentUser()->id();

        // if ($uid == 1) $uid++;

        $sql = "SELECT * FROM smtprofiles s WHERE s.ID = :id";
        $success = $db->query($sql, array(':id' => $uid))->fetch();

        if (!$success) {
            $sql = "INSERT INTO smtprofiles (ID) VALUES (:id)";
            $success = $db->query($sql, array(':id' => $uid));
        }

        return $success;
    }

    public static function member_status() {

        $output .= 'You are not registered as a SMT member. First fill in your'
             . ' profile data and then click the link to join SMT. ';

        $member = self::member();

        if (!$member) return $output;

        if (self::is_active_member()) {

            $output = 'Your SMT membership is up to date.';
            $output .= 'Your membership type is ' . $member->items . '.';
            $output .= 'Your membership expiration date is ' . $member->Date_renewed . '.';

        }
        else if ($member->payment_status === 'Pending')  {
            $output = 'Your SMT membership payment is pending.';
        }
        else if ($member->payment_status === 'Failed')  {
            $output = 'Your SMT membership payment has failed.';
        }
        else {
            $output = 'You are registered as a SMT member, but your payment'
                . ' has not yet been processed.';
        }

        return $output;
    }

}
