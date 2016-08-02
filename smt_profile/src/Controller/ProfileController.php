<?php

/**
 * @file
 * Contains \Drupal\smt_profile\Controller\ProfileController
 */

namespace Drupal\smt_profile\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
// use Drupal\smt_profile\MemberUtils;

class ProfileController extends ControllerBase {

    public function welcome() {

        $db = Database::getConnection();

        $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
        $uid = $user->get('uid')->value;
        // if ( $uid == 1) $uid++;

        $sql = "SELECT * FROM members s WHERE s.ID = :id";
        $data = $db->query($sql, array(':id' => $uid))->fetch();

        $output = "<h3>Welcome, ";
        $output .= $data->fname . " " . $data->lname . ", ";
        $output .=  "to your personal SMT page.</h3><br> This page is your account home. You can stay here and review your
        information or go the main page of the site and look around (click on home in the upper left corner).  With this new
        website, there may be some problems -- if you find anything amiss contact <a href='mailto:vlong@uchicago.edu'>Victoria Long</a>.";

        $output .= "<p><ul><li>Your membership renewal date is " . $data->Date_renewed . ". ";
        $output .= "</li><li>Your account is " . $data->items ;

        if ($data->fname2 <> "") {
            $output .= " with " . $data->fname2 . " " . $data->lname2 . ".";
        }
        else {
            $output .= ".";
        }

        $output .= "</li><li>";

        if ($data->smtdirectory == 1) {
            $output .= "You are in the smt directory.";
        }
        else {
            $output .= "You are not in the smt directory (you can change this by clicking on 'My profile' above.)";
        }

        $output .= "</li></ul>";
        $output .= "<p>To renew your membership or change your information, click on menu item <b>My Profile</b> and/or <b>Join/Renew</b>.</p>";

        return array(
            '#type' => 'markup',
            '#markup' => $this->t($output),
        );
    }
}
