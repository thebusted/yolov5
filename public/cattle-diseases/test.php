<?php
$url = 'https://images.theconversation.com/files/577190/original/file-20240221-22-67ggd8.jpg?ixlib=rb-4.1.0&rect=26%2C8%2C5964%2C3979&q=20&auto=format&w=320&fit=clip&dpr=2&usm=12&cs=strip';
$url = strtok($url, '?');
echo basename($url);


