# InstaLive-PHP

Thanks to mgp25 for Instagram-API.
Thanks to machacker16 for the live script part.

A PHP script to go in live Instagramam with any streaming program that supports RTMP!

# Install 

- Install PHP
- Install Composer
- run composer install into directory
- set username and password inside off config.php
- run the script with live.php
- copy Stream-URL and Stream-Key and paste them into your streaming software.

# OBS Setup

- Go to the "Stream" section of your OBS Settings
- Set "Stream Type" to "Custom Streaming Server"
- Set the "URL" field to the stream url you got from the script
- Set the "Stream key" field to the stream key you got from the script
- Make Sure "Use Authentication" is unchecked and press "OK"
- Start Streaming in OBS
- To stop streaming, run the "stop" command in your terminal and then press "Stop Streaming" in OBS

* Note: To emulate the exact content being sent to Instagram, set your OBS canvas size to 720x1280. This can be done by going to Settings->Video and editing Base Canvas Resolution to "720x1280".

# Terms and conditions

- You will NOT use this API for marketing purposes (spam, botting, harassment, massive bulk messaging...).
- We do NOT give support to anyone who wants to use this API to send spam or commit other crimes.
- We reserve the right to block any user of this repository that does not meet these conditions.

## Legal

This code is in no way affiliated with, authorized, maintained, sponsored or endorsed by Instagram or any of its affiliates or subsidiaries. This is an independent and unofficial API. Use at your own risk.
