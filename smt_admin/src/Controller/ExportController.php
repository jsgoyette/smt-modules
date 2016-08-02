<?php

/**
 * @file
 * Contains \Drupal\smt_admin\Controller\ExportController
 */

namespace Drupal\smt_admin\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\smt_admin\MemberUtils;

class ExportController extends ControllerBase {

    protected static $sqlQuery = "SELECT m.ID, m.fname, m.lname, m.title, m.add1, m.add2, m.add3, m.city, m.state, m.zip, m.country, u.mail as email, m.phone, m.fname2, m.lname2, m.gender, m.gender_explain, m.ethnicity, m.employment, m.dept, m.rank, m.invoice, m.date_created, m.Date_renewed, m.payment_status, m.payment_amount, m.items, m.quantities, m.notes, m.confguide, m.smtdirectory, m.shippingoptions, m.modified FROM members m INNER JOIN users_field_data u ON m.ID = u.uid WHERE m.ID > 1";

    /**
     * Callback for /smtadmin/export
     * Create a paged list of all SMT members from the SMT members database.
     * Create links to edit and to delete that person
     */
    public function content() {

        $output = '<p><b>Download from the following csv export files:</b></p><ul>';

        $exportRoutes = array(
            'Export all members' => 'smt_admin.exportMembersCsv',
            'Export active members' => 'smt_admin.exportActiveMembersCsv',
            'Export lapsed members' => 'smt_admin.exportLapsedMembersCsv',
        );

        foreach ($exportRoutes as $label => $route) {
            $url = Url::fromRoute($route, array());
            $output .= '<li>' . \Drupal::l($label, $url) . '</li>';
        }

        $output .= '</ul>';

        return array(
            'markup' => array(
                '#type' => 'markup',
                '#markup' => $this->t($output),
            ),
        );
    }

    protected function downloadCsv($filename, $rows) {

        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename={$filename}");

        // build header
        if (sizeof($rows)) {
            $header = array();
            foreach ($rows[0] as $fieldName => $value) {
                $header[] = $fieldName;
            }
            echo implode(',', $header) ."\r\n";
        }

        foreach ($rows as $idx => $row) {

            $row = (array) $row;

            foreach ($row as $key => $value) {
                $value = Html::decodeEntities(strip_tags($value));
                $value = str_replace(',', ' ', $value);
                $value = str_replace(array("\n", "\r"), ' ', $value);
                $row[$key] = $value;
            }

            echo implode(',', $row) ."\r\n";
        }

        die();
    }

    public function exportMembersCsv() {

        $db = Database::getConnection();
        $sql = self::$sqlQuery;

        $rows = $db->query($sql)->fetchAll();

        return self::downloadCsv('members.csv', $rows);

    }

    public function exportActiveMembersCsv() {

        $db = Database::getConnection();

        $today = date('Y-m-d');
        $sql = self::$sqlQuery . " AND m.Date_renewed >= ?";

        $rows = $db->query($sql, array($today))->fetchAll();

        return self::downloadCsv('members-active.csv', $rows);

    }

    public function exportLapsedMembersCsv() {

        $db = Database::getConnection();

        $today = date('Y-m-d');
        $sql = self::$sqlQuery . " AND m.Date_renewed < ?";

        $rows = $db->query($sql, array($today))->fetchAll();

        return self::downloadCsv('members-lapsed.csv', $rows);

    }
}
