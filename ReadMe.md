# Behat Mail Extension #

## Supported Drivers ##

<ul>
    <li>
        <a href="https://github.com/ddeboer/imap">IMAP</a>
    </li>
</ul>

## Installation ##

#### This extension has not been tested yet. To use the extension in your Behat testing environment you have to download it via composer which is not possible at the moment. 

## Configure Your Context ##

1) Implement the MailAwareContext in your feature context

2) Use the MailTrait in your context.


````
use BehatMailExtension\Context\MailAwareContext;
use BehatMailExtension\Context\MailTrait;

class FeatureContext implements MailAwareContext 
{
    use MailTrait;
    
    ...
````

Using the mail trait will add a mail property to your feature context.

## behat.yml ##

### IMAP ###

Add the MailExtension to your behat.yaml file:

````
default:
    extensions:
        BehatMailExtension\ServiceContainer\MailExtension:
            driver: imap //required
            server: 'imap.gmail.com' //required
            port: //defaults to '993' //required
            flags: //defaults to '/imap/ssl/validate-cert' //required 
            username: //required
            password: //required
````

## Usage ##

Access the mail property from your feature context to test any emails sent.

````
    /**
     * @Then I should receive a welcome email
     */
    public function iShouldReceiveAWelcomeEmail()
    {
        $message = $this->MailDriverInterface->getMessages();

        PHPUnit_Framework_Assert::assertEquals('Welcome!', $message->getSubject());
        PHPUnit_Framework_Assert::assertContains('Please confirm your account', $message->getBodyText());
    }
````    
    
#### The IMAP Driver API ####

The mail driver, accessible via the mail property on the feature context, offers the following methods.
These methods are described in the MailDriverInterface.

<ul>
    <li>
       getMailbox()
    </li>
    <li>
       getMailboxes()
    </li>
    <li>
       analyzeMailbox()
    </li>
    <li>
       analyzeMailboxes()
    </li>
    <li>
       setMailboxFlag()
    </li>                  
    <li>
        getMessages()
    </li>
    <li>
       sendMessage()
    </li>
    <li>
       sendMessages()
    </li>    
    <li>
       searchMessages()
    </li>
    <li>
        moveMessage()
    </li>
    <li>
        downloadMessageAttachments()
    </li>
    <li>
       deleteMessage()
    </li>
    <li>
        deleteMessages() (This is called automatically after scenarios tagged @mail)
    </li>
</ul>

#### The Message API ####

The mail driver will return a message object with the following API:

<ul>
    <li>getTo()</li>
    <li>getFrom()</li>
    <li>getDate() (DateTimeImmutable)</li>
    <li>getSubject()</li>
    <li>isAnswered()</li>
    <li>isDeleted()</li>
    <li>isDraft()</li>
    <li>isSeen()</li>
    <li>getBodyHtml()</li>
    <li>getBodyText()</li>
    <li>markAsSeen()</li>
    <li>setFlag()</li>
    <li>clearFlag()</li>
    <li>getAttachements()</li>
</ul>