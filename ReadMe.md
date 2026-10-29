# Behat Mail Extension #

## Supported Drivers ##

<ul>
    <li>
        <a href="https://github.com/ddeboer/imap">IMAP</a>
    </li>
</ul>

## Installation ##

````
{
     "require": {
            "pmaasz/behat-mail-extension": "dev-master"
        },
    
    "repositories": [
            {
                "type": "vcs",
                "url": "https://github.com/pmaasz/BehatMailExtension.git"
            }
        ]
}
````

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
            driver: imap                        # optional, defaults to "imap"
            server: 'imap.gmail.com'            # optional, defaults to "localhost"
            port: 993                           # optional, defaults to 993
            flags: '/imap/ssl/validate-cert'    # optional, defaults to "/imap/ssl/validate-cert"
            username: 'you@example.com'         # required
            password: 'secret'                  # required
````

`sent_mailbox` optionally selects the mailbox used by `sendMessage()` and `sendMessages()`.
It defaults to `Sent`; for Gmail, use `'[Gmail]/Sent Mail'`.

## Usage ##

Access the mail property from your feature context to test any emails sent.

````
    /**
     * @Then I should receive a welcome email
     */
    public function iShouldReceiveAWelcomeEmail()
    {
        $mailbox = $this->mail->getMailbox('INBOX');
        $messages = $this->mail->getMessages($mailbox);
        $message = $messages->current();

        \PHPUnit\Framework\Assert::assertSame('Welcome!', $message->getSubject());
        \PHPUnit\Framework\Assert::assertStringContainsString(
            'Please confirm your account',
            $message->getBodyText()
        );
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
        deleteMessages() (no automatic cleanup is performed after scenarios)
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
    <li>getAttachments()</li>
</ul>

## Star History
<a href="https://star-history.com/#pmaasz/behatmailextension&Date">
 <picture>
   <source media="(prefers-color-scheme: dark)" srcset="https://api.star-history.com/svg?repos=pmaasz/behatmailextension&type=Date&theme=dark" />
   <source media="(prefers-color-scheme: light)" srcset="https://api.star-history.com/svg?repos=pmaasz/behatmailextension&type=Date" />
   <img alt="Star History Chart" src="https://api.star-history.com/svg?repos=pmaasz/behatmailextension&type=Date" />
 </picture>
</a>