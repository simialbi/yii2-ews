<?php
/**
 * @package yii2-ews
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\ews;

use jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfBaseFolderIdsType;
use jamesiarmes\PhpEws\Client;
use jamesiarmes\PhpEws\Enumeration\DefaultShapeNamesType;
use jamesiarmes\PhpEws\Enumeration\DistinguishedFolderIdNameType;
use jamesiarmes\PhpEws\Enumeration\ItemQueryTraversalType;
use jamesiarmes\PhpEws\Request\FindItemType;
use jamesiarmes\PhpEws\Response\FindItemResponseMessageType;
use jamesiarmes\PhpEws\Type\CalendarViewType;
use jamesiarmes\PhpEws\Type\DistinguishedFolderIdType;
use jamesiarmes\PhpEws\Type\EmailAddressType;
use jamesiarmes\PhpEws\Type\ItemResponseShapeType;
use simialbi\yii2\ews\models\CalendarEvent;
use Yii;
use yii\base\Component;
use yii\validators\EmailValidator;

/**
 * Class Service
 * @package simialbi\yii2\ews
 *
 * @property-read Client $client
 */
class Service extends Component
{
    /**
     * @var string The url to the exchange server you wish to connect to, without the protocol. Example:
     *     mail.example.com.
     */
    public $server;
    /**
     * @var string The user to connect to the server with. This is usually the local portion of the users email address.
     * Example: "user" if the email address is "user@example.com"
     */
    public $username;
    /**
     * @var string The user's plain-text password.
     */
    public $password;
    /**
     * @var array The mailboxes to check
     */
    public $mailboxes = [];

    /**
     * @var Client EWS Client instance.
     */
    private $_client;

    /**
     * {@inheritDoc}
     */
    public function init()
    {
        parent::init();

        array_unshift($this->mailboxes, $this->username);
        $this->mailboxes = array_unique($this->mailboxes);

        $validator = new EmailValidator(['enableIDN' => function_exists('idn_to_ascii')]);
        foreach ($this->mailboxes as $k => $mailbox) {
            if (!$validator->validate($mailbox)) {
                Yii::warning("Mailbox '$mailbox' is not a valid email address", __METHOD__);
                unset($this->mailboxes[$k]);
            }
        }
    }

    /**
     * @return Client
     */
    public function getClient(): Client
    {
        if (empty($this->_client) || !$this->_client instanceof Client) {
            $this->_client = new Client($this->server, $this->username, $this->password);
        }

        return $this->_client;
    }

    /**
     * Get all calendar events from a given date time range
     *
     * @param string|\DateTime|integer $start A string parsable by [[strtotime]], [[DateTime]] instance or unix timestamp
     * @param string|\DateTime|integer $end A string parsable by [[strtotime]], [[DateTime]] instance or unix timestamp
     *
     * @return CalendarEvent[]
     */
    public function getCalendarEvents($start = '-15 days', $end = '+15 days'): array
    {
        $client = $this->getClient();
        $calendarItems = [];

        $start = Yii::$app->formatter->asTimestamp($start);
        $end = Yii::$app->formatter->asTimestamp($end);

        foreach ($this->mailboxes as $mailbox) {
            $calendarItems[$mailbox] = [];
            $request = new FindItemType();
            $request->Traversal = ItemQueryTraversalType::SHALLOW;
            $request->ItemShape = new ItemResponseShapeType();
            $request->ItemShape->BaseShape = DefaultShapeNamesType::ALL_PROPERTIES;
            $request->CalendarView = new CalendarViewType();
            $request->CalendarView->StartDate = date('c', $start);
            $request->CalendarView->EndDate = date('c', $end);
            $request->ParentFolderIds = new NonEmptyArrayOfBaseFolderIdsType();
            $request->ParentFolderIds->DistinguishedFolderId = new DistinguishedFolderIdType();
            $request->ParentFolderIds->DistinguishedFolderId->Id = DistinguishedFolderIdNameType::CALENDAR;
            $request->ParentFolderIds->DistinguishedFolderId->Mailbox = new EmailAddressType();
            $request->ParentFolderIds->DistinguishedFolderId->Mailbox->EmailAddress = $mailbox;

            $response = $client->FindItem($request);

            /** @var FindItemResponseMessageType $message */
            $message = $response->ResponseMessages->FindItemResponseMessage;

            if (is_array($message)) {
                $message = array_shift($message);
            }

            if ($message->RootFolder->TotalItemsInView > 0) {
                foreach ($message->RootFolder->Items->CalendarItem as $event) {
                    $calendarItems[$mailbox][] = CalendarEvent::fromEvent($event);
                }
            }
        }

        return $calendarItems;
    }
}
