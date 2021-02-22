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
use simialbi\yii2\ews\models\CalendarEvent;
use Yii;
use yii\base\Component;

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
     * @var Client EWS Client instance.
     */
    private $_client;

    /**
     * @return Client
     */
    public function getClient()
    {
        if (empty($this->_client) || !$this->_client instanceof Client) {
            $this->_client = new Client($this->server, $this->username, $this->password);
        }

        return $this->_client;
    }

    /**
     *
     * @return CalendarEvent[]
     */
    public function getCalendarEvents()
    {
        $client = $this->getClient();

        $request = new FindItemType();
        $request->Traversal = ItemQueryTraversalType::SHALLOW;
//        $request->ItemShape = new ItemResponseShapeType();
        $request->ItemShape->BaseShape = DefaultShapeNamesType::ALL_PROPERTIES;
        $request->CalendarView = new CalendarViewType();
        $request->CalendarView->StartDate = date('c', strtotime('-15 days'));
        $request->CalendarView->EndDate = date('c', strtotime('+15 days'));
        $request->ParentFolderIds = new NonEmptyArrayOfBaseFolderIdsType();
        $request->ParentFolderIds->DistinguishedFolderId = new DistinguishedFolderIdType();
        $request->ParentFolderIds->DistinguishedFolderId->Id = DistinguishedFolderIdNameType::CALENDAR;

        $response = $client->FindItem($request);

        /** @var FindItemResponseMessageType $message */
        $message = $response->ResponseMessages->FindItemResponseMessage;

        if ($message->RootFolder->TotalItemsInView > 0) {
            $events = $message->RootFolder->Items->CalendarItem;
            $calendarItems = [];
            foreach ($events as $event) {
                $calendarItems[] = CalendarEvent::fromEvent($event);
            }
            return $calendarItems;
        } else {
            return [];
        }
    }
}
