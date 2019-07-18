# Behat Mail Extension #

## Supported Drivers ##

<ul>
    <li>
        <a href="https://github.com/ddeboer/imap">IMAP</a>
    </li>
</ul>

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
            driver: imap
            server: 'imap.gmail.com' //required
            port: //defaults to '993'
            flags: //defaults to '/imap/ssl/validate-cert'
            parameters:  //Connection parameters, the following (string) keys maybe used to set one or more connection parameters:
                         //DISABLE_AUTHENTICATOR - Disable authentication properties

````

## Usage ##

The Behat Mail Extension will automatically clear messages from the inbox when runing scenarios tagged with @mail

````
Feature: App Registration
  In order to join the site
  As a guest
  I want to register for an account

  @mail
  Scenario: Register an account
    Given I am a guest
    When I register for an account
    Then I should receive a welcome email
````  

Access the mail property from your feature context to test any emails sent.

````
    /**
     * @Then I should receive a welcome email
     */
    public function iShouldReceiveAWelcomeEmail()
    {
        $message = $this->mail->getLatestMessage();

        PHPUnit_Framework_Assert::assertEquals('Welcome!', $message->subject());
        PHPUnit_Framework_Assert::assertContains('Please confirm your account', $message->plainText());
    }
````    
    
#### The Mail Driver API ####

The mail driver, accessible via the mail property on the feature context, offers the following methods:

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
    <li>setFlag(string $flag)</li>
    <li>clearFlag(string $flag)</li>
</ul>