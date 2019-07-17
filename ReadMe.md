# Behat Mail Extension #

## Supported Drivers ##

<ul>
    <li>
        <a href="https://mailcatcher.me/">MailCatcher</a>
    </li>
    <li>
        <a href="https://mailtrap.io/">MailTrap</a>
    </li>
</ul>

## Configure Your Context ##

1) Implement the MailAwareContext in your feature context

2) Use the Mail trait in your context.


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

Chose one of the following configurations for your behat.yml file.

#### Defaults ####

If no drivers are specified the following defaults will be used:

driver: mailcatcher
base_uri: localhost
http_port: 1080

````
default:
    extensions:
        tPayne\BehatMailExtension\ServiceContainer\MailExtension
````

#### MailCatcher ####

Add the MailExtension to your behat.yml file:

````
default:
    extensions:
        tPayne\BehatMailExtension\ServiceContainer\MailExtension:
            driver: mailcatcher
            base_uri: localhost # optional
            http_port: 1080 # optional
````

#### MailTrap ####

Add the MailExtension to your behat.yaml file:

````
default:
    extensions:
        tPayne\BehatMailExtension\ServiceContainer\MailExtension:
            driver: mailtrap
            api_key: MAIL_TRAP_KEY
            mailbox_id: MAILBOX_ID
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
        getMessages()
    </li>
    <li>
        getLatestMessage()
    </li>
    <li>
        deleteMessages() (This is called automatically after scenarios tagged @mail)
    </li>
</ul>

#### The Message API ####

The mail driver will return a message object with the following API:

<ul>
    <li>to()</li>
    <li>from()</li>
    <li>subject()</li>
    <li>plainText()</li>
    <li>html()</li>
    <li>date()</li>
</ul>