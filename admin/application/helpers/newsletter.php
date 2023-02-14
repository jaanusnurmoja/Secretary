<?php

/**
 * @version     3.2.0
 * @package     com_secretary
 *
 * @author       Fjodor Schaefer (schefa.com)
 * @copyright    Copyright (C) 2015-2017 Fjodor Schaefer. All rights reserved.
 * @license      MIT License
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 * 
 */

namespace Secretary\Helpers;

use JText;

// No direct access
defined('_JEXEC') or die;

abstract class Newsletter
{

    /**
     * Method to send a newsletter template
     *
     * @param number $newsletterid
     * @return boolean|number
     */
    public static function sendNewsletter($newsletterid)
    {
        // Get newsletter
        $newsletter = \Secretary\Database::getQuery('templates', $newsletterid);

        // Get newsletter list + contacts
        $newsletterContacts = \Secretary\Database::getQuery('newsletter', $newsletter->catid, 'listID', 'contactID', 'loadColumn');

        $msg = false;

        // check if someone has subscribed
        if (empty($newsletterContacts)) {
            return $msg;
        }

        // Loop Contacts
        foreach ($newsletterContacts as $contactID) {
            $contact = \Secretary\Database::getQuery('subjects', $contactID);
            if (!isset($contact))
                continue;
            $text = \Secretary\Helpers\Templates::transformText($newsletter->text, array('subject' => $contact->id));
            if (!empty($contact->email)) {
                $msg += (int) \Secretary\Email::email($contact->firstname . ' ' . $contact->lastname, $contact->email, $newsletter->title, $text);
            }
        }

        return $msg;
    }

    /**
     * Method to get contacts who subscribe to a newsletter list
     * 
     * @param number $newsletterListID
     * @return object[]|array list of contacts
     */
    public static function getNewsletterContacts($newsletterListID)
    {
        $db = \Secretary\Database::getDBO();

        $query = 'SELECT id,firstname,lastname FROM ' . $db->qn('#__secretary_subjects');
        $query .= 'WHERE id IN (SELECT contactID FROM ' . $db->qn('#__secretary_newsletter') . ' WHERE listID = ' . (int) $newsletterListID . ')';
        $db->setQuery($query);
        $result = $db->loadObjectList();

        return (!empty($result)) ? $result : array();
    }

    public static function addContactToNewsletter($contactid, $newsletterid)
    {
        $app = \Secretary\Joomla::getApplication();
        $db = \Secretary\Database::getDBO();

        $db->setQuery('SELECT * FROM #__secretary_newsletter WHERE ' . $db->qn('listID') . '=' . (int) $newsletterid . ' AND ' . $db->qn('contactID') . '=' . (int) $contactid);
        $exists = $db->loadResult();

        $sql = $db->getQuery(true);
        if (empty($exists)) {
            $sql->insert($db->qn("#__secretary_newsletter"))
                ->set($db->qn("listID") . "=" . $db->escape((int) $newsletterid))
                ->set($db->qn("contactID") . "=" . $db->escape((int) $contactid));
            try {
                $db->setQuery($sql);
                $result = $db->query();
                return $result;
            } catch (\RuntimeException $e) {
                $app->enqueueMessage($e->getMessage(), 'error');
                return false;
            }
        } else {
            return true;
        }
    }

    public static function removeContactFromAllNewsletters($contactid)
    {
        $app = \Secretary\Joomla::getApplication();
        $db = \Secretary\Database::getDBO();
        $sql = $db->getQuery(true);

        $sql->delete($db->qn("#__secretary_newsletter"));
        $sql->where($db->qn("contactID") . "=" . $db->escape((int) $contactid));
        try {
            $db->setQuery($sql);
            $result = $db->query();
            return $result;
        } catch (\RuntimeException $e) {
            $app->enqueueMessage($e->getMessage(), 'error');
            return false;
        }
    }

    public static function removeContactFromNewsletter($contactid, $newsletter_id)
    {
        $app = \Secretary\Joomla::getApplication();
        $db = \Secretary\Database::getDBO();
        $sql = $db->getQuery(true);
        if (!empty($exists)) {
            $sql->delete($db->qn("#__secretary_newsletter"))
                ->where($db->qn("listID") . "=" . intval($newsletter_id))
                ->where($db->qn("contactID") . "=" . $db->escape((int) $contactid));
            try {
                $db->setQuery($sql);
                $result = $db->query();
                return $result;
            } catch (\RuntimeException $e) {
                $app->enqueueMessage($e->getMessage(), 'error');
                return false;
            }
        } else {
            return true;
        }
    }

    public static function removeNewsletterFromContact($contactid, $newsletterid = NULL)
    {
        $db = \Secretary\Database::getDBO();
        $subject = \Secretary\Database::getQuery('subjects', (int) $contactid);

        if (empty($subject))
            return JText::sprintf('COM_SECRETARY_ERROR_CHECK_THIS', 'Email is not in a newsletter');

        // Get Old
        $oldFields = $subject->fields;

        $newsletterIds = array();
        $found = false;
        if ($fields = json_decode($oldFields)) {
            // unset same newsletter
            if (strpos($oldFields, "newsletter") !== false) {
                foreach ($fields as $key => $field) {
                    if (is_numeric($key)) {
                        if (isset($newsletterid) && $field[3] == 'newsletter' && $field[2] == $newsletterid) {
                            unset($fields[$key]);
                            $found = true;
                            $newsletterIds[] = $field[2];
                        } elseif (!isset($newsletterid) && $field[3] == 'newsletter') {
                            unset($fields[$key]);
                            $found = true;
                            $newsletterIds[] = $field[2];
                        }
                    }
                }
            }
            $oldFields = json_encode($fields);
        }

        if (!$found)
            return JText::sprintf('COM_SECRETARY_ERROR_CHECK_THIS', 'Email is not in a newsletter');

        // Remove Contact from Newsletters
        foreach ($newsletterIds as $newsletter_id)
            \Secretary\Helpers\Newsletter::removeContactFromNewsletter($contactid, (int) $newsletter_id);

        // Set New
        $updateQuery = $db->getQuery(true);
        $updateQuery->update($db->qn("#__secretary_subjects"));
        $updateQuery->set($db->qn("fields") . "=" . $db->quote($oldFields));
        $updateQuery->where($db->qn("id") . "=" . $db->escape($contactid));

        try {
            $db->setQuery($updateQuery);
            $db->execute();
        } catch (\RuntimeException $e) {
            $app->enqueueMessage($e->getMessage(), 'error');
            return JText::_('COM_SECRETARY_ERROR_OCCURED');
        }

        return JText::_('COM_SECRETARY_NEWSLETTER_UNSUBSCRIBED');
    }

    public static function refreshNewsletterListToContacts($newsletterListID, $contacts, $batch = false)
    {
        $app = \Secretary\Joomla::getApplication();
        $db = \Secretary\Database::getDBO();
        $contactsIds = array_unique($contacts);

        // Geld field ID
        $query = $db->getQuery(true);
        $query->select('id,title');
        $query->from($db->qn("#__secretary_fields"));
        $query->where($db->qn("hard") . " LIKE " . $db->quote("newsletter"));
        $db->setQuery($query);
        $field = $db->loadObject();

        // Clear newsletter table
        $db->setQuery('DELETE FROM ' . $db->qn("#__secretary_newsletter") . ' WHERE ' . $db->qn('listID') . ' = ' . intval($newsletterListID));
        $db->execute();

        // Input Field
        $input = array($field->id, JText::_($field->title), $newsletterListID, 'newsletter');
        $contactsNewsletter = array();

        // Kontakte Felder update
        foreach ($contactsIds as $contactid) {
            // New Field input
            $newFields = array($input);

            // Get Old
            $oldFields = \Secretary\Database::getQuery('subjects', $contactid, 'id', 'fields', 'loadResult');

            if ($fields = json_decode($oldFields)) {
                // unset same newsletter
                if (strpos($oldFields, "newsletter") !== false) {
                    foreach ($fields as $key => $field) {
                        if (is_numeric($key) && $field[3] == 'newsletter' && $field[2] == $newsletterListID) {
                            unset($fields[$key]);
                        }
                    }
                }
                $newFields = array_merge($newFields, $fields);
            }

            // Set New
            $upd = $db->getQuery(true);
            $upd->update($db->qn("#__secretary_subjects"))
                ->set($db->qn("fields") . "=" . $db->quote(json_encode($newFields)))
                ->where($db->qn("id") . "=" . $db->escape($contactid));

            try {
                $db->setQuery($upd);
                $db->query();
            } catch (\RuntimeException $e) {
                $app->enqueueMessage($e->getMessage(), 'error');
                continue;
            }

            // Contact in der Newsletter Liste vermerken
            \Secretary\Helpers\Newsletter::addContactToNewsletter($contactid, $newsletterListID);
        }
    }
}