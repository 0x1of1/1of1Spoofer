# Email Threading Manual

## Overview

Email threading is the process of linking emails together to form a continuous conversation. When spoofing emails, proper threading ensures that replies appear in the correct conversation view in the recipient's email client.

## How Email Threading Works

Email threading relies on three key components:

1. **Subject Line**: Adding "Re:" at the beginning of the subject
2. **Message-ID Headers**: Unique identifiers for each email
3. **Thread Formatting**: Visual presentation of the conversation

### Technical Implementation

Email clients use these specific email headers to maintain threads:

- **Message-ID**: A unique identifier for each email
- **References**: Contains the Message-ID of the original email and all subsequent emails in the thread
- **In-Reply-To**: Contains the Message-ID of the immediate previous email

## Step-by-Step Guide for Manual Threading

### Starting a New Thread

1. Send your initial email with a normal subject
2. **Important**: Save the Message-ID from the sent email (displayed after successful sending)

### First Reply

1. Enter "Re: [Original Subject]" in the subject line
2. In the **References** field, enter the Message-ID from the original email
3. In the **In-Reply-To** field, enter the same Message-ID
4. For authenticity, you can copy the original message text and preface each line with ">"

### Second Reply and Beyond (Long Threads)

1. Enter "Re: [Original Subject]" in the subject line
2. In the **References** field, enter **ALL** previous Message-IDs separated by spaces
   - Format: `<id1@domain.com> <id2@domain.com> <id3@domain.com>`
3. In the **In-Reply-To** field, enter **ONLY** the Message-ID of the immediately previous email
4. For visual authenticity, include the entire conversation history:

   ```
   Your new reply text here

   On [Date], [Name] wrote:
   > Previous reply text
   >
   > On [Earlier Date], [Original Name] wrote:
   >> Original message text
   ```

## Practical Example of a Long Thread

### Step 1: Original Email

- **Subject**: Security Update Required
- **Message-ID**: `<abc123@example.com>`

### Step 2: First Reply

- **Subject**: Re: Security Update Required
- **References**: `<abc123@example.com>`
- **In-Reply-To**: `<abc123@example.com>`
- **Message-ID**: `<def456@example.com>`
- **Body**:

  ```
  Thanks for the notification. What steps do I need to take?

  On May 1, 2023, IT Support wrote:
  > Please update your security credentials within 24 hours.
  > This is required for all employees.
  ```

### Step 3: Second Reply

- **Subject**: Re: Security Update Required
- **References**: `<abc123@example.com> <def456@example.com>`
- **In-Reply-To**: `<def456@example.com>`
- **Message-ID**: `<ghi789@example.com>`
- **Body**:

  ```
  Please follow the instructions in the attachment.

  On May 1, 2023, John Smith wrote:
  > Thanks for the notification. What steps do I need to take?
  >
  > On May 1, 2023, IT Support wrote:
  >> Please update your security credentials within 24 hours.
  >> This is required for all employees.
  ```

### Step 4: Third Reply

- **Subject**: Re: Security Update Required
- **References**: `<abc123@example.com> <def456@example.com> <ghi789@example.com>`
- **In-Reply-To**: `<ghi789@example.com>`
- **Body**:

  ```
  I've completed the update. Please confirm it worked.

  On May 1, 2023, IT Support wrote:
  > Please follow the instructions in the attachment.
  >
  > On May 1, 2023, John Smith wrote:
  >> Thanks for the notification. What steps do I need to take?
  >>
  >> On May 1, 2023, IT Support wrote:
  >>> Please update your security credentials within 24 hours.
  >>> This is required for all employees.
  ```

## Finding Message-IDs in Different Email Clients

### Gmail

1. Open the email
2. Click the three dots in the top right
3. Select "Show original"
4. Look for the "Message-ID:" field

### Outlook

1. Open the email
2. Click File > Properties
3. Find the "Internet headers" box
4. Look for the "Message-ID:" line

### Apple Mail

1. Open the email
2. Select View > Message > All Headers
3. Look for the "Message-ID:" field

## Troubleshooting

If threading isn't working:

1. **Check Message-ID Format**: Must be wrapped in angle brackets `<>`
2. **Verify References Chain**: All previous Message-IDs must be included
3. **Subject Line**: Must start with "Re:" (case insensitive)
4. **Email Client Limitations**: Some clients have unique threading rules

## Advanced Techniques

### For Very Long Threads

When threads exceed 5-6 emails, some email clients may truncate the References header. In this case:

1. Always include the **first** Message-ID in the thread
2. Always include the **last 3-4** Message-IDs in the thread
3. You can omit middle Message-IDs if necessary

### Generating Consistent Message-IDs

For maximum authenticity, generate Message-IDs that match the domain you're spoofing:

Format: `<random-string@domain-being-spoofed.com>`

Example: If spoofing from `example.com`, use Message-IDs like `<123456.789@example.com>`
