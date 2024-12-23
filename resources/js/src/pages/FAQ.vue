<script setup>
// No complex logic or reactive data here — this is a static FAQ page.
</script>

<template>
    <div class="faq-wrapper">
        <h1>FAQ (Frequently Asked Questions)</h1>
        <p class="last-updated"><strong>Last updated:</strong> 23.12.2024</p>

        <p>
            Below is a collection of common questions and answers about Calories365. If you don’t find the information you need,
            feel free to contact us at <em>mostlysunny1488@gmail.com</em>.
        </p>

        <!-- 1. What is Calories365? -->
        <h2>1. What is Calories365?</h2>
        <p>
            <strong>Calories365</strong> is an online service and Telegram bot designed for convenient food diary management
            and nutritional control. We help you track calories, proteins, fats, and carbohydrates (macronutrients) in products
            and meals, along with providing daily, weekly, and monthly stats and analytics.
        </p>

        <!-- 2. Which features are available in the app? -->
        <h2>2. Which features are available in the app?</h2>
        <ul>
            <li><strong>Food diary management:</strong> Add products, specify the amount consumed, and get automated calorie and macronutrient calculations.</li>
            <li><strong>Voice input via Telegram bot:</strong> Simply tell the bot what you ate; it uses OpenAI to recognize speech and suggests adding the product to your diary.</li>
            <li><strong>Stats and reports:</strong> Track how many calories and nutrients you’ve consumed over various time periods (today, week, month).</li>
            <li><strong>Product search (MeiliSearch):</strong> Find nearly any product in our comprehensive database of both standard and user-created entries.</li>
            <li><strong>Edit and delete entries:</strong> If you need to correct or remove a record, you can do so quickly and easily.</li>
        </ul>

        <!-- 3. Do I need to download an app on my smartphone? -->
        <h2>3. Do I need to download an app on my smartphone?</h2>
        <p>
            Not necessarily. We offer:
        </p>
        <ul>
            <li>A <strong>web application</strong> that runs in your browser on both computers and phones.</li>
            <li>A <strong>Telegram bot</strong> that you can interact with directly — no extra software needed.</li>
        </ul>
        <p>
            In the future, we may release a dedicated mobile app, but our core functionality is already available via the web and the bot.
        </p>

        <!-- 4. How does voice recognition work? -->
        <h2>4. How does voice recognition work?</h2>
        <p>
            We use OpenAI’s API (such as Whisper or GPT-based models) to convert your voice message into text. Then the bot
            analyzes that text, finds relevant products in the database, and suggests adding them to your diary. We do not
            store your audio files after processing.
        </p>

        <!-- 5. What are the available sign-in methods? -->
        <h2>5. What are the available sign-in methods?</h2>
        <ul>
            <li>Email and password (standard registration form).</li>
            <li>Google OAuth (sign in quickly without creating a new password).</li>
            <li>(Under development) Linking your Telegram account to your web profile for seamless data sync.</li>
        </ul>

        <!-- 6. Do you store the details of what I eat? -->
        <h2>6. Do you store the details of what I eat?</h2>
        <p>
            We only keep the information necessary for the food diary functionality: your list of products, quantities,
            and consumption times. This data is accessible only in your personal account (and via the bot when authenticated).
            We do not share it with third parties or make it public.
        </p>

        <!-- 7. Is it free to use the app? -->
        <h2>7. Is it free to use the app?</h2>
        <p>
            The core features (calorie tracking, adding products, basic stats) are currently free. We plan to introduce
            a premium subscription in the future, offering expanded features (like advanced analytics, image-based recognition, etc.).
            We will announce any changes in advance.
        </p>

        <!-- 8. I found an error in the calorie table. How can I fix it? -->
        <h2>8. I found an error in the calorie table. How can I fix it?</h2>
        <ul>
            <li>If it’s a user-created product, you can edit it in the web interface.</li>
            <li>Otherwise, let us know via the feedback form or write “Error in product” to our bot. We’ll review and update the data.</li>
        </ul>

        <!-- 9. Can I scan barcodes or recognize food by photos? -->
        <h2>9. Can I scan barcodes or recognize food by photos?</h2>
        <p>
            Photo recognition and barcode scanning are on our development roadmap. We’re considering adding these features
            when we release a mobile app or a PWA (Progressive Web App). Currently, they are not implemented.
        </p>

        <!-- 10. How do you ensure data security? -->
        <h2>10. How do you ensure data security?</h2>
        <p>
            We use HTTPS for secure data transmission between the client and server, and limit database access to trusted personnel/services.
            For Google OAuth users, we do not store passwords; authentication happens directly through Google.
            Our goal is to maintain high standards of security and privacy.
        </p>

        <!-- 11. What if I don’t want to use the Telegram bot? -->
        <h2>11. What if I don’t want to use the Telegram bot?</h2>
        <p>
            That’s totally fine. You can rely solely on the web application, which provides the same fundamental functionalities:
            adding products, editing records, reviewing stats, etc.
        </p>

        <!-- 12. Which platforms/devices are supported? -->
        <h2>12. Which platforms/devices are supported?</h2>
        <ul>
            <li><strong>Web interface:</strong> works on all modern desktop and mobile browsers.</li>
            <li><strong>Telegram bot:</strong> available on any device that supports Telegram (iOS, Android, Windows, macOS, Linux, etc.).</li>
        </ul>

        <!-- 13. How can I contact support? -->
        <h2>13. How can I contact support?</h2>
        <ul>
            <li>Email us at: <em>mostlysunny1488@gmail.com</em></li>
            <li>Use the “Contact Us” button in the web interface</li>
            <li>In the Telegram bot, use <em>/help</em> or <em>/support</em></li>
        </ul>

        <!-- 15. Do you plan to grow further? -->
        <h2>14. Do you plan to grow further?</h2>
        <p>
            Yes, we are continuously improving both the bot and the web application. We’re developing premium features
            (advanced analytics, photo recognition) and exploring additional integrations. Stay tuned for updates and announcements!
        </p>

        <h2>Conclusion</h2>
        <p>
            We hope this FAQ covers your main questions about <strong>Calories365</strong>. If you have more questions or ideas,
            feel free to reach out via any of our contact channels. Thank you for using Calories365 to track your diet and health!
        </p>
    </div>
</template>

<style scoped lang="scss">
.faq-wrapper {
    max-width: 800px;
    margin: 40px auto;
    padding: 0 20px;
    line-height: 1.6;

    h1 {
        font-size: 1.8rem;
        margin-bottom: 1rem;
    }

    h2 {
        font-size: 1.4rem;
        margin-top: 2rem;
        margin-bottom: 0.5rem;
        font-weight: 600;
    }

    .last-updated {
        font-style: italic;
        margin-bottom: 1.5rem;
    }

    ul, ol {
        margin-left: 1.5rem;
        margin-bottom: 1rem;
    }

    p {
        margin-bottom: 1rem;
    }
}
</style>
