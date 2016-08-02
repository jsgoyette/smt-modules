<?php

/**
 * @file
 * Contains \Drupal\smt_profile\Reminders.
 */

namespace Drupal\smt_profile;

use Drupal\Core\Database\Database;

/**
 * Standard member util methods
 */
class Reminders {

    // does the user have a member record
    public static function execute() {

        \Drupal::logger('smt_profile')->notice('SMT Member Reminders - cron run');

        $db = Database::getConnection();

        $members_sql = "SELECT ID, Date_renewed FROM members
            WHERE Date_renewed < DATE_ADD(NOW(), INTERVAL 31 DAY)
                AND Date_renewed > NOW()";

        $rows = $db->query($members_sql, array())->fetchAll();

        foreach ($rows as $idx => $row) {

            $id = $row->ID;
            $renewed = $row->Date_renewed;
            $days = (strtotime($renewed) - strtotime(date("Y-m-d"))) / (60 * 60 * 24);

            $reminder_due = false;

            // check if the first reminder is due
            if ($days >= 25 && $days <= 30) {

                $sql = "SELECT first_reminder FROM members_reminders WHERE ID = ? and renewed = ?";
                $data = $db->query($sql, array($id, $renewed))->fetch();

                if (!$data) {
                    $reminder_due = true;
                    $reminder_days = 'thirty';
                    $sql = "INSERT INTO members_reminders VALUES (?, ?, ?, ?)";
                    $result = $db->query($sql, array($id, $renewed, date('Y-m-d H:i:s'), '1970-01-01 01:00:00'));
                }
            }

            // check if the second reminder is due
            if ($days <= 7 && $days >= 0) {

                $sql = "SELECT second_reminder FROM members_reminders WHERE ID = ? and renewed = ?";
                $data = $db->query($sql, array($id, $renewed))->fetch();

                if (!$data) {
                    $reminder_due = true;
                    $reminder_days = 'seven';
                    $sql = "INSERT INTO members_reminders VALUES (?, ?, ?, ?)";
                    $result = $db->query($sql, array($id, $renewed, '1970-01-01 01:00:00', date('Y-m-d H:i:s')));
                }
                else if ($data->second_reminder == '1970-01-01 01:00:00') {
                    $reminder_due = true;
                    $reminder_days = 'seven';
                    $sql = "UPDATE members_reminders SET second_reminder = ? WHERE ID = ? and renewed = ?";
                    $result = $db->query($sql, array(date('Y-m-d H:i:s'), $id, $renewed));
                }
            }

            if ($reminder_due) {
                self::sendMail($id, $renewed, $reminder_days);
            }
        }
    }

    private static function sendMail($id, $renewed, $days) {

        $db = Database::getConnection();
        $mailManager = \Drupal::service('plugin.manager.mail');

        $sql = "SELECT mail FROM users_field_data WHERE uid = ?";
        $user = $db->query($sql, array($id))->fetch();

        if (empty($user)) return;

        \Drupal::logger('smt_profile')->notice('SMT Member Reminders - sending email for ID ' . $id);

        $key = 'membershipexpiry';
        $to = $user->mail;

        $params = array(
            'ID' => $ID . ' mail is ' . $data->mail,
            'days' => $reminder_days,
        );

        $langcode = 'en';
        $send = true;

        // return $mailManager->mail('smt_profile', $key, $to, $langcode, $params, null, $send);
    }
}
