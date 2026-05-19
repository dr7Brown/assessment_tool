-- ============================================================
-- AceICT: 200 MCQ Questions — Year 1, S1/SS3 Connecting and Communicating Online
-- Ghana GES ICT Curriculum
-- Sub-strand: SS1 — Productivity Tools
-- Topics: Internet and WWW (IP addresses, DNS, URLs, browsers, search engines)
-- Email (Email structure, professional,writing, CC/BCC/attachments, email,safety)
-- Online Communication (Video conferencing, social,media, choosing the right, tool, responsible, use)
-- Netiquette and Digital Citizenship( 9 elements of digital,citizenship, rules of online, behaviour, digital divide)
-- Online Safety (Phishing, identity theft,cyberbullying, sakawa,malware, protective habits)
-- Digital Health (Eye strain, posture, sleep and screens, social media and,mental health)

-- Difficulty: 40 Easy, 80 Medium, 80 Hard
-- All options stored as separate rows in question_options
-- school_id=1, author_id=1, year_group=1
-- ============================================================

-- ────────────────────────────────────────────────────────────
-- EASY QUESTIONS (1–40)
-- ────────────────────────────────────────────────────────────

-- i want you to generate 200 mcq questions for year 1, Strand 1, sub-strand 3 (year1_s1_ss3). 
-- the questions should be based on the Ghana SHS curriculum for that sub-strands, 
-- and should be in the format of a question followed by four answer options (A, B, C, D) 
-- with one correct answer. please provide the questions in a SQL insert statement format 
-- to be added to the questions table. each question should have a unique id 
-- starting from 555.
-- question formate below: INSERT INTO questions (id,school_id,author_id,type,sub_strand,topic,bloom_level,difficulty,year_group,marks,question_text,explanation,is_active) VALUES
-- example: (555,1,1,'mcq','S1/SS3','Internet and WWW',1,'easy',1,1,'What does DNS stand for?','DNS stands for Domain Name System, which translates domain names into IP addresses.',1),

-- answer format below: INSERT INTO question_options (question_id,option_label,option_text,is_correct,sort_order) VALUES
-- example: (555,'A','Domain Name System',0,0),(555,'B','Digital Network Service',0,1),(555,'C','Data Naming Structure',0,2),(555,'D','Direct Network Search',1,3),


-- EASY QUESTIONS (IDs 555–594)
SET FOREIGN_KEY_CHECKS=0;

INSERT INTO questions (id,school_id,author_id,type,sub_strand,topic,bloom_level,difficulty,year_group,marks,question_text,explanation,is_active) VALUES
(555,1,1,'mcq','S1/SS3','Internet and WWW',1,'easy',1,1,'What does DNS stand for?','DNS stands for Domain Name System, which translates domain names into IP addresses.',1),
(556,1,1,'mcq','S1/SS3','Internet and WWW',1,'easy',1,1,'Which protocol is used to transfer web pages?','HTTP is the protocol used to transfer web pages.',1),
(557,1,1,'mcq','S1/SS3','Internet and WWW',1,'easy',1,1,'What does URL stand for?','URL stands for Uniform Resource Locator.',1),
(558,1,1,'mcq','S1/SS3','Internet and WWW',1,'easy',1,1,'Which browser is an example of open-source software?','Mozilla Firefox is an open-source browser.',1),
(559,1,1,'mcq','S1/SS3','Internet and WWW',1,'easy',1,1,'What is the unique number assigned to each device on a network called?','It is called an IP address.',1),

(560,1,1,'mcq','S1/SS3','Email',1,'easy',1,1,'Which field in an email hides recipients from each other?','BCC hides recipients from each other.',1),
(561,1,1,'mcq','S1/SS3','Email',1,'easy',1,1,'What does CC stand for in email?','CC stands for Carbon Copy.',1),
(562,1,1,'mcq','S1/SS3','Email',1,'easy',1,1,'Which part of an email contains the main message?','The body contains the main message.',1),
(563,1,1,'mcq','S1/SS3','Email',1,'easy',1,1,'Which is a safe practice when opening email attachments?','Only open attachments from trusted sources.',1),
(564,1,1,'mcq','S1/SS3','Email',1,'easy',1,1,'Which of these is a professional email greeting?','“Dear Sir/Madam” is a professional greeting.',1),

(565,1,1,'mcq','S1/SS3','Online Communication',1,'easy',1,1,'Which tool is best for real-time video meetings?','Video conferencing tools are best for real-time meetings.',1),
(566,1,1,'mcq','S1/SS3','Online Communication',1,'easy',1,1,'Which platform is mainly used for social networking?','Facebook is mainly used for social networking.',1),
(567,1,1,'mcq','S1/SS3','Online Communication',1,'easy',1,1,'Which tool is best for instant text communication?','Instant messaging tools are best for text communication.',1),
(568,1,1,'mcq','S1/SS3','Online Communication',1,'easy',1,1,'Which online tool is best for sharing photos?','Social media platforms are best for sharing photos.',1),
(569,1,1,'mcq','S1/SS3','Online Communication',1,'easy',1,1,'Which tool allows screen sharing during meetings?','Video conferencing tools allow screen sharing.',1),

(570,1,1,'mcq','S1/SS3','Netiquette and Digital Citizenship',1,'easy',1,1,'What does netiquette mean?','Netiquette means rules of polite online behavior.',1),
(571,1,1,'mcq','S1/SS3','Netiquette and Digital Citizenship',1,'easy',1,1,'Which of these is NOT part of digital citizenship?','Ignoring others online is not part of digital citizenship.',1),
(572,1,1,'mcq','S1/SS3','Netiquette and Digital Citizenship',1,'easy',1,1,'Which element of digital citizenship deals with online safety?','Digital security deals with online safety.',1),
(573,1,1,'mcq','S1/SS3','Netiquette and Digital Citizenship',1,'easy',1,1,'Which element of digital citizenship deals with respecting others online?','Digital etiquette deals with respecting others.',1),
(574,1,1,'mcq','S1/SS3','Netiquette and Digital Citizenship',1,'easy',1,1,'Which element of digital citizenship deals with access to technology?','Digital access deals with availability of technology.',1),

(575,1,1,'mcq','S1/SS3','Online Safety',1,'easy',1,1,'What is phishing?','Phishing is tricking people into giving personal information.',1),
(576,1,1,'mcq','S1/SS3','Online Safety',1,'easy',1,1,'Which of these is an example of malware?','Viruses are examples of malware.',1),
(577,1,1,'mcq','S1/SS3','Online Safety',1,'easy',1,1,'What is cyberbullying?','Cyberbullying is bullying through digital platforms.',1),
(578,1,1,'mcq','S1/SS3','Online Safety',1,'easy',1,1,'Which of these is a safe online habit?','Using strong passwords is a safe habit.',1),
(579,1,1,'mcq','S1/SS3','Online Safety',1,'easy',1,1,'What is identity theft?','Identity theft is stealing someone’s personal information.',1),

(580,1,1,'mcq','S1/SS3','Digital Health',1,'easy',1,1,'Which of these can cause eye strain?','Staring at screens for long periods causes eye strain.',1),
(581,1,1,'mcq','S1/SS3','Digital Health',1,'easy',1,1,'Which posture is best when using a computer?','Sitting upright with back support is best.',1),
(582,1,1,'mcq','S1/SS3','Digital Health',1,'easy',1,1,'Which of these can affect sleep?','Using screens late at night affects sleep.',1),
(583,1,1,'mcq','S1/SS3','Digital Health',1,'easy',1,1,'Which of these is a healthy digital habit?','Taking regular breaks is a healthy habit.',1),
(584,1,1,'mcq','S1/SS3','Digital Health',1,'easy',1,1,'Which of these can affect mental health?','Excessive social media use can affect mental health.',1),

(585,1,1,'mcq','S1/SS3','Internet and WWW',1,'easy',1,1,'Which search engine is owned by Google?','Google Search is owned by Google.',1),
(586,1,1,'mcq','S1/SS3','Internet and WWW',1,'easy',1,1,'Which protocol is secure version of HTTP?','HTTPS is the secure version of HTTP.',1),
(587,1,1,'mcq','S1/SS3','Email',1,'easy',1,1,'Which part of an email shows the topic?','The subject line shows the topic.',1),
(588,1,1,'mcq','S1/SS3','Online Communication',1,'easy',1,1,'Which app is commonly used for chatting?','WhatsApp is commonly used for chatting.',1),
(589,1,1,'mcq','S1/SS3','Netiquette and Digital Citizenship',1,'easy',1,1,'Which element of digital citizenship deals with learning online?','Digital literacy deals with learning online.',1),
(590,1,1,'mcq','S1/SS3','Online Safety',1,'easy',1,1,'Which of these protects against malware?','Antivirus software protects against malware.',1),
(591,1,1,'mcq','S1/SS3','Digital Health',1,'easy',1,1,'Which of these reduces eye strain?','Using the 20-20-20 rule reduces eye strain.',1),
(592,1,1,'mcq','S1/SS3','Internet and WWW',1,'easy',1,1,'Which company created the Chrome browser?','Google created the Chrome browser.',1),
(593,1,1,'mcq','S1/SS3','Email',1,'easy',1,1,'Which is a polite way to end an email?','“Yours sincerely” is a polite ending.',1),
(594,1,1,'mcq','S1/SS3','Online Communication',1,'easy',1,1,'Which tool is best for group discussions online?','Discussion forums are best for group discussions.',1);

INSERT INTO question_options (question_id,option_label,option_text,is_correct,sort_order) VALUES
(555,'A','Domain Name System',1,0),(555,'B','Digital Network Service',0,1),(555,'C','Data Naming Structure',0,2),(555,'D','Direct Network Search',0,3),
(556,'A','HTTP',1,0),(556,'B','FTP',0,1),(556,'C','SMTP',0,2),(556,'D','POP3',0,3),
(557,'A','Uniform Resource Locator',1,0),(557,'B','Universal Routing Link',0,1),(557,'C','User Reference List',0,2),(557,'D','Unified Record Locator',0,3),
(558,'A','Mozilla Firefox',1,0),(558,'B','Google Chrome',0,1),(558,'C','Safari',0,2),(558,'D','Microsoft Edge',0,3),
(559,'A','IP Address',1,0),(559,'B','MAC Address',0,1),(559,'C','URL',0,2),(559,'D','DNS',0,3),

(560,'A','CC',0,0),(560,'B','BCC',1,1),(560,'C','To',0,2),(560,'D','Reply-To',0,3),
(561,'A','Carbon Copy',1,0),(561,'B','Central Contact',0,1),(561,'C','Copy Contact',0,2),(561,'D','Common Communication',0,3),
(562,'A','Subject',0,0),(562,'B','Body',1,1),(562,'C','Header',0,2),(562,'D','Signature',0,3),
(563,'A','Open all attachments',0,0),(563,'B','Only open trusted attachments',1,1),(563,'C','Ignore attachments',0,2),(563,'D','Forward attachments to friends',0,3),
(564,'A','Hey buddy',0,0),(564,'B','Dear Sir/Madam',1,1),(564,'C','Yo!',0,2),(564,'D','Hiya',0,3),

(565,'A','Email',0,0),(565,'B','Video Conferencing',1,1),(565,'C','Social Media',0,2),(565,'D','Instant Messaging',0,3),
(566,'A','Facebook',1,0),(566,'B','Zoom',0,1),(566,'C','Outlook',0,2),(566,'D','Word',0,3),
(567,'A','Instant Messaging',1,0),(567,'B','Video Conferencing',0,1),(567,'C','Email',0,2),(567,'D','Social Media',0,3),
(568,'A','Social Media',1,0),(568,'B','Word Processor',0,1),(568,'C','Spreadsheet',0,2),(568,'D','Database',0,3),
(569,'A','Video Conferencing',1,0),(569,'B','Email',0,1),(569,'C','Search Engine',0,2),(569,'D','Word Processor',0,3),

(570,'A','Rules of online behavior',1,0),(570,'B','Computer hardware',0,1),(570,'C','Programming language',0,2),(570,'D','Internet speed',0,3),
(571,'A','Digital etiquette',0,0),(571,'B','Digital literacy',0,1),(571,'C','Ignoring others online',1,2),(571,'D','Digital security',0,3),
(572,'A','Digital security',1,0),(572,'B','Digital commerce',0,1),(572,'C','Digital access',0,2),(572,'D','Digital literacy',0,3),
(573,'A','Digital etiquette',1,0),(573,'B','Digital commerce',0,1),(573,'C','Digital law',0,2),(573,'D','Digital health',0,3),
(574,'A','Digital access',1,0),(574,'B','Digital commerce',0,1),(574,'C','Digital law',0,2),(574,'D','Digital literacy',0,3),

(575,'A','Phishing',1,0),(575,'B','Malware',0,1),(575,'C','Spam',0,2),(575,'D','Firewall',0,3),
(576,'A','Virus',1,0),(576,'B','Firewall',0,1),(576,'C','Password',0,2),(576,'D','Router',0,3),
(577,'A','Cyberbullying',1,0),(577,'B','Phishing',0,1),(577,'C','Malware',0,2),(577,'D','Spam',0,3),
(578,'A','Using strong passwords',1,0),(578,'B','Sharing passwords',0,1),(578,'C','Clicking unknown links',0,2),(578,'D','Ignoring updates',0,3),
(579,'A','Identity theft',1,0),(579,'B','Phishing',0,1),(579,'C','Spam',0,2),(579,'D','Firewall',0,3),

(580,'A','Staring at screens too long',1,0),(580,'B','Walking outdoors',0,1),(580,'C','Listening to music',0,2),(580,'D','Drinking water',0,3),
(581,'A','Sitting upright with back support',1,0),(581,'B','Slouching',0,1),(581,'C','Lying down',0,2),(581,'D','Standing bent',0,3),
(582,'A','Using screens late at night',1,0),(582,'B','Reading books',0,1),(582,'C','Eating healthy food',0,2),(582,'D','Exercising',0,3),
(583,'A','Taking regular breaks',1,0),(583,'B','Never resting',0,1),(583,'C','Ignoring posture',0,2),(583,'D','Skipping meals',0,3),
(584,'A','Excessive social media use',1,0),(584,'B','Drinking water',0,1),(584,'C','Walking outdoors',0,2),(584,'D','Listening to music',0,3),

(585,'A','Google Search',1,0),(585,'B','Bing',0,1),(585,'C','Yahoo',0,2),(585,'D','DuckDuckGo',0,3),
(586,'A','HTTPS',1,0),(586,'B','FTP',0,1),(586,'C','SMTP',0,2),(586,'D','POP3',0,3),
(587,'A','Subject line',1,0),(587,'B','Body',0,1),(587,'C','Signature',0,2),(587,'D','Header',0,3),
(588,'A','WhatsApp',1,0),(588,'B','Excel',0,1),(588,'C','Word',0,2),(588,'D','PowerPoint',0,3),
(589,'A','Digital literacy',1,0),(589,'B','Digital commerce',0,1),(589,'C','Digital law',0,2),(589,'D','Digital etiquette',0,3),

(590,'A','Antivirus software',1,0),(590,'B','Spam',0,1),(590,'C','Phishing',0,2),(590,'D','Malware',0,3),
(591,'A','20-20-20 rule',1,0),(591,'B','Ignoring breaks',0,1),(591,'C','Slouching posture',0,2),(591,'D','Sleeping late',0,3),
(592,'A','Google',1,0),(592,'B','Microsoft',0,1),(592,'C','Apple',0,2),(592,'D','Mozilla',0,3),
(593,'A','Yours sincerely',1,0),(593,'B','See ya',0,1),(593,'C','Bye',0,2),(593,'D','Catch you later',0,3),
(594,'A','Discussion forums',1,0),(594,'B','Word processor',0,1),(594,'C','Spreadsheet',0,2),(594,'D','Presentation software',0,3);

INSERT INTO questions (id,school_id,author_id,type,sub_strand,topic,bloom_level,difficulty,year_group,marks,question_text,explanation,is_active) VALUES
(595,1,1,'mcq','S1/SS3','Internet and WWW',2,'medium',1,1,'Which part of a URL indicates the domain name?','The domain name identifies the website address in a URL.',1),
(596,1,1,'mcq','S1/SS3','Internet and WWW',2,'medium',1,1,'What is the main function of a search engine?','Search engines help users find information on the web.',1),
(597,1,1,'mcq','S1/SS3','Internet and WWW',2,'medium',1,1,'Which IP version uses 128-bit addresses?','IPv6 uses 128-bit addresses.',1),
(598,1,1,'mcq','S1/SS3','Internet and WWW',2,'medium',1,1,'Which protocol is used to send emails?','SMTP is used to send emails.',1),
(599,1,1,'mcq','S1/SS3','Internet and WWW',2,'medium',1,1,'Which part of a browser displays the URL?','The address bar displays the URL.',1),

(600,1,1,'mcq','S1/SS3','Email',2,'medium',1,1,'Why should you use a clear subject line in emails?','A clear subject line helps the recipient understand the purpose.',1),
(601,1,1,'mcq','S1/SS3','Email',2,'medium',1,1,'Which email field should you use to send a copy to multiple recipients openly?','CC is used to send copies openly.',1),
(602,1,1,'mcq','S1/SS3','Email',2,'medium',1,1,'Why is it important to check grammar in professional emails?','Good grammar ensures clarity and professionalism.',1),
(603,1,1,'mcq','S1/SS3','Email',2,'medium',1,1,'Which of these is NOT a safe email practice?','Clicking unknown links is unsafe.',1),
(604,1,1,'mcq','S1/SS3','Email',2,'medium',1,1,'Why should you avoid using slang in professional emails?','Slang reduces professionalism and clarity.',1),

(605,1,1,'mcq','S1/SS3','Online Communication',2,'medium',1,1,'Which tool is best for collaborative document editing online?','Google Docs is best for collaborative editing.',1),
(606,1,1,'mcq','S1/SS3','Online Communication',2,'medium',1,1,'Which platform is best for professional networking?','LinkedIn is best for professional networking.',1),
(607,1,1,'mcq','S1/SS3','Online Communication',2,'medium',1,1,'Which tool allows both video and instant messaging?','Microsoft Teams allows both video and messaging.',1),
(608,1,1,'mcq','S1/SS3','Online Communication',2,'medium',1,1,'Which online tool is best for sharing academic research?','ResearchGate is best for sharing academic research.',1),
(609,1,1,'mcq','S1/SS3','Online Communication',2,'medium',1,1,'Which tool is best for online group discussions in schools?','Google Classroom is best for group discussions.',1),

(610,1,1,'mcq','S1/SS3','Netiquette and Digital Citizenship',2,'medium',1,1,'Why is respecting copyright important online?','Respecting copyright prevents plagiarism and supports creators.',1),
(611,1,1,'mcq','S1/SS3','Netiquette and Digital Citizenship',2,'medium',1,1,'Which element of digital citizenship deals with buying and selling online?','Digital commerce deals with buying and selling online.',1),
(612,1,1,'mcq','S1/SS3','Netiquette and Digital Citizenship',2,'medium',1,1,'Why is digital literacy important?','Digital literacy helps users use technology effectively.',1),
(613,1,1,'mcq','S1/SS3','Netiquette and Digital Citizenship',2,'medium',1,1,'Which element of digital citizenship deals with obeying online laws?','Digital law deals with obeying online laws.',1),
(614,1,1,'mcq','S1/SS3','Netiquette and Digital Citizenship',2,'medium',1,1,'Why is digital etiquette important?','It ensures respectful communication online.',1),

(615,1,1,'mcq','S1/SS3','Online Safety',2,'medium',1,1,'Which of these is a sign of phishing?','Unexpected emails asking for personal info are phishing.',1),
(616,1,1,'mcq','S1/SS3','Online Safety',2,'medium',1,1,'Which of these is a way to protect your password?','Using a mix of letters, numbers, and symbols protects passwords.',1),
(617,1,1,'mcq','S1/SS3','Online Safety',2,'medium',1,1,'Which of these is NOT malware?','Firewall is not malware.',1),
(618,1,1,'mcq','S1/SS3','Online Safety',2,'medium',1,1,'Why should you avoid sharing personal info online?','It prevents identity theft.',1),
(619,1,1,'mcq','S1/SS3','Online Safety',2,'medium',1,1,'Which of these is a safe way to browse online?','Using secure websites (HTTPS) is safe.',1),

(620,1,1,'mcq','S1/SS3','Digital Health',2,'medium',1,1,'Why should you take breaks when using computers?','Breaks prevent eye strain and fatigue.',1),
(621,1,1,'mcq','S1/SS3','Digital Health',2,'medium',1,1,'Which of these is a healthy sleep habit?','Avoiding screens before bed is healthy.',1),
(622,1,1,'mcq','S1/SS3','Digital Health',2,'medium',1,1,'Why is posture important when using computers?','Good posture prevents back pain.',1),
(623,1,1,'mcq','S1/SS3','Digital Health',2,'medium',1,1,'Which of these is a healthy way to use social media?','Limiting screen time is healthy.',1),
(624,1,1,'mcq','S1/SS3','Digital Health',2,'medium',1,1,'Why is balancing online and offline life important?','It supports mental and physical health.',1),

(625,1,1,'mcq','S1/SS3','Internet and WWW',2,'medium',1,1,'Which search engine focuses on privacy?','DuckDuckGo focuses on privacy.',1),
(626,1,1,'mcq','S1/SS3','Internet and WWW',2,'medium',1,1,'Which protocol is used to download files from servers?','FTP is used to download files.',1),
(627,1,1,'mcq','S1/SS3','Email',2,'medium',1,1,'Which of these is a professional closing in emails?','“Best regards” is a professional closing.',1),
(628,1,1,'mcq','S1/SS3','Online Communication',2,'medium',1,1,'Which tool is best for online learning?','Moodle is best for online learning.',1),
(629,1,1,'mcq','S1/SS3','Netiquette and Digital Citizenship',2,'medium',1,1,'Which element of digital citizenship deals with protecting health online?','Digital health deals with protecting health online.',1),
(630,1,1,'mcq','S1/SS3','Online Safety',2,'medium',1,1,'Which of these is a safe way to store files?','Using cloud storage with strong passwords is safe.',1),
(631,1,1,'mcq','S1/SS3','Digital Health',2,'medium',1,1,'Which of these reduces screen fatigue?','Adjusting screen brightness reduces fatigue.',1),
(632,1,1,'mcq','S1/SS3','Internet and WWW',2,'medium',1,1,'Which company created the Safari browser?','Apple created Safari.',1),
(633,1,1,'mcq','S1/SS3','Email',2,'medium',1,1,'Which of these is NOT a professional email practice?','Using emojis in formal emails is not professional.',1),
(634,1,1,'mcq','S1/SS3','Online Communication',2,'medium',1,1,'Which tool is best for sharing presentations online?','Google Slides is best for sharing presentations.',1);

INSERT INTO question_options (question_id,option_label,option_text,is_correct,sort_order) VALUES
(595,'A','Domain name',1,0),(595,'B','Protocol',0,1),(595,'C','Path',0,2),(595,'D','Fragment',0,3),
(596,'A','Find information on the web',1,0),(596,'B','Send emails',0,1),(596,'C','Store files',0,2),(596,'D','Play videos',0,3),
(597,'A','IPv6',1,0),(597,'B','IPv4',0,1),(597,'C','DNS',0,2),(597,'D','URL',0,3),
(598,'A','SMTP',1,0),(598,'B','HTTP',0,1),(598,'C','FTP',0,2),(598,'D','POP3',0,3),
(599,'A','Address bar',1,0),(599,'B','Menu bar',0,1),(599,'C','Status bar',0,2),(599,'D','Toolbar',0,3),

(600,'A','Helps recipient understand purpose',1,0),(600,'B','Makes email longer',0,1),(600,'C','Adds decoration',0,2),(600,'D','Shows sender’s mood',0,3),
(601,'A','CC',1,0),(601,'B','BCC',0,1),(601,'C','To',0,2),(601,'D','Reply-To',0,3),
(602,'A','Ensures clarity and professionalism',1,0),(602,'B','Makes email funny',0,1),(602,'C','Adds emojis',0,2),(602,'D','Shows sender’s age',0,3),
(603,'A','Clicking unknown links',1,0),(603,'B','Using strong passwords',0,1),(603,'C','Checking grammar',0,2),(603,'D','Using professional greetings',0,3),
(604,'A','Slang reduces professionalism',1,0),(604,'B','Slang makes email formal',0,1),(604,'C','Slang improves clarity',0,2),(604,'D','Slang is required',0,3),

(605,'A','Google Docs',1,0),(605,'B','Microsoft Word offline',0,1),(605,'C','Notepad',0,2),(605,'D','Calculator',0,3),
(606,'A','LinkedIn',1,0),(606,'B','Instagram',0,1),(606,'C','TikTok',0,2),(606,'D','Snapchat',0,3),
(607,'A','Microsoft Teams',1,0),(607,'B','WordPad',0,1),(607,'C','Excel',0,2),(607,'D','Paint',0,3),
(608,'A','ResearchGate',1,0),(608,'B','Facebook',0,1),(608,'C','Twitter',0,2),(608,'D','Instagram',0,3),
(609,'A','Google Classroom',1,0),(609,'B','MS Paint',0,1),(609,'C','Excel',0,2),(609,'D','Word',0,3),

(610,'A','Prevents plagiarism and supports creators',1,0),(610,'B','Makes internet faster',0,1),(610,'C','Improves grammar',0,2),(610,'D','Blocks spam',0,3),
(611,'A','Digital commerce',1,0),(611,'B','Digital etiquette',0,1),(611,'C','Digital law',0,2),(611,'D','Digital literacy',0,3),
(612,'A','Helps users use technology effectively',1,0),(612,'B','Improves handwriting',0,1),(612,'C','Teaches cooking',0,2),(612,'D','Improves sports skills',0,3),
(613,'A','Digital law',1,0),(613,'B','Digital etiquette',0,1),(613,'C','Digital commerce',0,2),(613,'D','Digital literacy',0,3),
(614,'A','Ensures respectful communication online',1,0),(614,'B','Improves internet speed',0,1),(614,'C','Teaches programming',0,2),(614,'D','Blocks viruses',0,3),

(615,'A','Unexpected emails asking for info',1,0),(615,'B','Emails from friends',0,1),(615,'C','School newsletters',0,2),(615,'D','Official notices',0,3),
(616,'A','Mix of letters, numbers, symbols',1,0),(616,'B','Simple words only',0,1),(616,'C','Birthdate only',0,2),(616,'D','Pet’s name only',0,3),
(617,'A','Firewall',1,0),(617,'B','Virus',0,1),(617,'C','Trojan',0,2),(617,'D','Spyware',0,3),
(618,'A','Prevents identity theft',1,0),(618,'B','Improves grammar',0,1),(618,'C','Speeds up internet',0,2),(618,'D','Saves battery',0,3),
(619,'A','Using secure websites (HTTPS)',1,0),(619,'B','Clicking unknown links',0,1),(619,'C','Sharing passwords',0,2),(619,'D','Ignoring updates',0,3),

(620,'A','Prevents eye strain and fatigue',1,0),(620,'B','Makes computer faster',0,1),(620,'C','Improves grammar',0,2),(620,'D','Saves electricity',0,3),
(621,'A','Avoiding screens before bed',1,0),(621,'B','Watching TV late',0,1),(621,'C','Using phone at midnight',0,2),(621,'D','Sleeping less',0,3),
(622,'A','Prevents back pain',1,0),(622,'B','Improves typing speed',0,1),(622,'C','Improves eyesight',0,2),(622,'D','Saves electricity',0,3),
(623,'A','Limiting screen time',1,0),(623,'B','Using social media all day',0,1),(623,'C','Sharing personal info',0,2),(623,'D','Ignoring privacy',0,3),
(624,'A','Supports mental and physical health',1,0),(624,'B','Improves internet speed',0,1),(624,'C','Teaches programming',0,2),(624,'D','Saves money',0,3),

(625,'A','DuckDuckGo',1,0),(625,'B','Google',0,1),(625,'C','Yahoo',0,2),(625,'D','Bing',0,3),
(626,'A','FTP',1,0),(626,'B','SMTP',0,1),(626,'C','POP3',0,2),(626,'D','HTTP',0,3),
(627,'A','Best regards',1,0),(627,'B','See ya',0,1),(627,'C','Bye',0,2),(627,'D','Catch you later',0,3),
(628,'A','Moodle',1,0),(628,'B','Excel',0,1),(628,'C','Word',0,2),(628,'D','Paint',0,3),
(629,'A','Digital health',1,0),(629,'B','Digital commerce',0,1),(629,'C','Digital law',0,2),(629,'D','Digital literacy',0,3),

(630,'A','Cloud storage with strong passwords',1,0),(630,'B','Leaving files on desktop',0,1),(630,'C','Sharing files publicly',0,2),(630,'D','Using weak passwords',0,3),
(631,'A','Adjusting screen brightness',1,0),(631,'B','Ignoring breaks',0,1),(631,'C','Slouching posture',0,2),(631,'D','Sleeping late',0,3),
(632,'A','Apple',1,0),(632,'B','Google',0,1),(632,'C','Microsoft',0,2),(632,'D','Mozilla',0,3),
(633,'A','Using emojis in formal emails',1,0),(633,'B','Clear subject line',0,1),(633,'C','Professional greeting',0,2),(633,'D','Checking grammar',0,3),
(634,'A','Google Slides',1,0),(634,'B','WordPad',0,1),(634,'C','Excel',0,2),(634,'D','Paint',0,3);

INSERT INTO questions (id,school_id,author_id,type,sub_strand,topic,bloom_level,difficulty,year_group,marks,question_text,explanation,is_active) VALUES
(635,1,1,'mcq','S1/SS3','Internet and WWW',2,'medium',1,1,'Which organization manages domain names globally?','ICANN manages domain names globally.',1),
(636,1,1,'mcq','S1/SS3','Internet and WWW',2,'medium',1,1,'Which protocol is used to receive emails?','POP3 is used to receive emails.',1),
(637,1,1,'mcq','S1/SS3','Internet and WWW',2,'medium',1,1,'Which part of a URL shows the protocol?','The beginning of a URL shows the protocol.',1),
(638,1,1,'mcq','S1/SS3','Internet and WWW',2,'medium',1,1,'Which search engine is owned by Microsoft?','Bing is owned by Microsoft.',1),
(639,1,1,'mcq','S1/SS3','Internet and WWW',2,'medium',1,1,'Which IP version uses 32-bit addresses?','IPv4 uses 32-bit addresses.',1),

(640,1,1,'mcq','S1/SS3','Email',2,'medium',1,1,'Why should you avoid using all caps in emails?','All caps can be seen as shouting.',1),
(641,1,1,'mcq','S1/SS3','Email',2,'medium',1,1,'Which email field is used for the main recipient?','The “To” field is for the main recipient.',1),
(642,1,1,'mcq','S1/SS3','Email',2,'medium',1,1,'Why should you proofread emails before sending?','Proofreading prevents mistakes and miscommunication.',1),
(643,1,1,'mcq','S1/SS3','Email',2,'medium',1,1,'Which of these is a professional way to start an email?','“Dear Mr. Mensah” is professional.',1),
(644,1,1,'mcq','S1/SS3','Email',2,'medium',1,1,'Why should you avoid sending large attachments?','Large attachments may not be delivered.',1),

(645,1,1,'mcq','S1/SS3','Online Communication',2,'medium',1,1,'Which tool is best for online surveys?','Google Forms is best for surveys.',1),
(646,1,1,'mcq','S1/SS3','Online Communication',2,'medium',1,1,'Which platform is best for sharing short videos?','TikTok is best for short videos.',1),
(647,1,1,'mcq','S1/SS3','Online Communication',2,'medium',1,1,'Which tool is best for online group chats?','WhatsApp groups are best for group chats.',1),
(648,1,1,'mcq','S1/SS3','Online Communication',2,'medium',1,1,'Which tool is best for online meetings in schools?','Zoom is best for online school meetings.',1),
(649,1,1,'mcq','S1/SS3','Online Communication',2,'medium',1,1,'Which tool is best for sharing large files online?','Google Drive is best for sharing large files.',1),

(650,1,1,'mcq','S1/SS3','Netiquette and Digital Citizenship',2,'medium',1,1,'Which element of digital citizenship deals with buying safely online?','Digital commerce deals with safe buying online.',1),
(651,1,1,'mcq','S1/SS3','Netiquette and Digital Citizenship',2,'medium',1,1,'Why is digital security important?','It protects users from online threats.',1),
(652,1,1,'mcq','S1/SS3','Netiquette and Digital Citizenship',2,'medium',1,1,'Which element of digital citizenship deals with respecting others’ privacy?','Digital etiquette deals with respecting privacy.',1),
(653,1,1,'mcq','S1/SS3','Netiquette and Digital Citizenship',2,'medium',1,1,'Why is digital access important?','It ensures equal opportunity to use technology.',1),
(654,1,1,'mcq','S1/SS3','Netiquette and Digital Citizenship',2,'medium',1,1,'Which element of digital citizenship deals with online health?','Digital health deals with online health.',1),

(655,1,1,'mcq','S1/SS3','Online Safety',2,'medium',1,1,'Which of these is a way to avoid phishing?','Do not click suspicious links.',1),
(656,1,1,'mcq','S1/SS3','Online Safety',2,'medium',1,1,'Which of these is a safe password habit?','Changing passwords regularly is safe.',1),
(657,1,1,'mcq','S1/SS3','Online Safety',2,'medium',1,1,'Which of these is NOT a safe online habit?','Sharing passwords is unsafe.',1),
(658,1,1,'mcq','S1/SS3','Online Safety',2,'medium',1,1,'Which of these is a way to protect against malware?','Installing antivirus software protects against malware.',1),
(659,1,1,'mcq','S1/SS3','Online Safety',2,'medium',1,1,'Which of these is a safe way to use public Wi-Fi?','Using a VPN is safe.',1),

(660,1,1,'mcq','S1/SS3','Digital Health',2,'medium',1,1,'Why should you adjust screen brightness?','It reduces eye strain.',1),
(661,1,1,'mcq','S1/SS3','Digital Health',2,'medium',1,1,'Which of these is a healthy way to use phones?','Limiting phone use before bed is healthy.',1),
(662,1,1,'mcq','S1/SS3','Digital Health',2,'medium',1,1,'Why should you sit upright when using computers?','It prevents back pain.',1),
(663,1,1,'mcq','S1/SS3','Digital Health',2,'medium',1,1,'Which of these is a healthy way to use social media?','Using social media moderately is healthy.',1),
(664,1,1,'mcq','S1/SS3','Digital Health',2,'medium',1,1,'Why should you avoid screens before sleep?','Screens affect sleep quality.',1),

(665,1,1,'mcq','S1/SS3','Internet and WWW',2,'medium',1,1,'Which company created the Edge browser?','Microsoft created Edge.',1),
(666,1,1,'mcq','S1/SS3','Internet and WWW',2,'medium',1,1,'Which protocol is used for secure file transfer?','SFTP is used for secure file transfer.',1),
(667,1,1,'mcq','S1/SS3','Email',2,'medium',1,1,'Which of these is a professional way to end an email?','“Kind regards” is professional.',1),
(668,1,1,'mcq','S1/SS3','Online Communication',2,'medium',1,1,'Which tool is best for online presentations?','Microsoft PowerPoint online is best.',1),
(669,1,1,'mcq','S1/SS3','Netiquette and Digital Citizenship',2,'medium',1,1,'Which element of digital citizenship deals with online buying and selling?','Digital commerce deals with online buying and selling.',1),
(670,1,1,'mcq','S1/SS3','Online Safety',2,'medium',1,1,'Which of these is a safe way to protect accounts?','Using two-factor authentication is safe.',1),
(671,1,1,'mcq','S1/SS3','Digital Health',2,'medium',1,1,'Which of these prevents neck pain?','Keeping screen at eye level prevents neck pain.',1),
(672,1,1,'mcq','S1/SS3','Internet and WWW',2,'medium',1,1,'Which company created the Opera browser?','Opera Software created Opera.',1),
(673,1,1,'mcq','S1/SS3','Email',2,'medium',1,1,'Which of these is NOT professional in emails?','Using nicknames in formal emails is not professional.',1),
(674,1,1,'mcq','S1/SS3','Online Communication',2,'medium',1,1,'Which tool is best for sharing notes online?','Evernote is best for sharing notes.',1);

INSERT INTO question_options (question_id,option_label,option_text,is_correct,sort_order) VALUES
(635,'A','ICANN',1,0),(635,'B','UNESCO',0,1),(635,'C','WHO',0,2),(635,'D','IMF',0,3),
(636,'A','POP3',1,0),(636,'B','SMTP',0,1),(636,'C','HTTP',0,2),(636,'D','FTP',0,3),
(637,'A','Beginning of URL',1,0),(637,'B','Domain name',0,1),(637,'C','Path',0,2),(637,'D','Fragment',0,3),
(638,'A','Bing',1,0),(638,'B','Google',0,1),(638,'C','Yahoo',0,2),(638,'D','DuckDuckGo',0,3),
(639,'A','IPv4',1,0),(639,'B','IPv6',0,1),(639,'C','DNS',0,2),(639,'D','URL',0,3),

(640,'A','All caps can be seen as shouting',1,0),(640,'B','All caps improves clarity',0,1),(640,'C','All caps is professional',0,2),(640,'D','All caps is required',0,3),
(641,'A','To field',1,0),(641,'B','CC field',0,1),(641,'C','BCC field',0,2),(641,'D','Reply-To field',0,3),
(642,'A','Prevents mistakes and miscommunication',1,0),(642,'B','Makes email longer',0,1),(642,'C','Adds emojis',0,2),(642,'D','Shows sender’s mood',0,3),
(643,'A','Dear Mr. Mensah',1,0),(643,'B','Hey dude',0,1),(643,'C','Yo!',0,2),(643,'D','Hiya',0,3),
(644,'A','Large attachments may not be delivered',1,0),(644,'B','Large attachments improve clarity',0,1),(644,'C','Large attachments are professional',0,2),(644,'D','Large attachments are required',0,3),

(645,'A','Google Forms',1,0),(645,'B','MS Paint',0,1),(645,'C','Excel',0,2),(645,'D','Word',0,3),
(646,'A','TikTok',1,0),(646,'B','LinkedIn',0,1),(646,'C','WordPad',0,2),(646,'D','Excel',0,3),
(647,'A','WhatsApp groups',1,0),(647,'B','MS Word',0,1),(647,'C','Excel',0,2),(647,'D','Paint',0,3),
(648,'A','Zoom',1,0),(648,'B','Notepad',0,1),(648,'C','Calculator',0,2),(648,'D','Paint',0,3),
(649,'A','Google Drive',1,0),(649,'B','MS Word',0,1),(649,'C','Excel',0,2),(649,'D','Paint',0,3),

(650,'A','Digital commerce',1,0),(650,'B','Digital etiquette',0,1),(650,'C','Digital law',0,2),(650,'D','Digital literacy',0,3),
(651,'A','Protects users from online threats',1,0),(651,'B','Improves grammar',0,1),(651,'C','Speeds up internet',0,2),(651,'D','Saves electricity',0,3),
(652,'A','Digital etiquette',1,0),(652,'B','Digital commerce',0,1),(652,'C','Digital law',0,2),(652,'D','Digital literacy',0,3),
(653,'A','Ensures equal opportunity to use technology',1,0),(653,'B','Improves handwriting',0,1),(653,'C','Teaches cooking',0,2),(653,'D','Improves sports skills',0,3),
(654,'A','Digital health',1,0),(654,'B','Digital commerce',0,1),(654,'C','Digital law',0,2),(654,'D','Digital literacy',0,3),

(655,'A','Do not click suspicious links',1,0),(655,'B','Click all links',0,1),(655,'C','Share passwords',0,2),(655,'D','Ignore updates',0,3),
(656,'A','Changing passwords regularly',1,0),(656,'B','Using simple words',0,1),(656,'C','Using birthdate only',0,2),(656,'D','Using pet’s name only',0,3),
(657,'A','Sharing passwords',1,0),(657,'B','Using strong passwords',0,1),(657,'C','Checking grammar',0,2),(657,'D','Using professional greetings',0,3),
(658,'A','Installing antivirus software',1,0),(658,'B','Ignoring updates',0,1),(658,'C','Clicking unknown links',0,2),(658,'D','Sharing passwords',0,3),
(659,'A','Using a VPN',1,0),(659,'B','Sharing passwords',0,1),(659,'C','Clicking unknown links',0,2),(659,'D','Ignoring updates',0,3),

(660,'A','Reduces eye strain',1,0),(660,'B','Improves grammar',0,1),(660,'C','Saves electricity',0,2),(660,'D','Speeds up internet',0,3),
(661,'A','Limiting phone use before bed',1,0),(661,'B','Using phone at midnight',0,1),(661,'C','Watching TV late',0,2),(661,'D','Sleeping less',0,3),
(662,'A','Prevents back pain',1,0),(662,'B','Improves typing speed',0,1),(662,'C','Improves eyesight',0,2),(662,'D','Saves electricity',0,3),
(663,'A','Using social media moderately',1,0),(663,'B','Using social media all day',0,1),(663,'C','Sharing personal info',0,2),(663,'D','Ignoring privacy',0,3),
(664,'A','Screens affect sleep quality',1,0),(664,'B','Screens improve sleep',0,1),(664,'C','Screens are required',0,2),(664,'D','Screens save electricity',0,3),

(665,'A','Microsoft',1,0),(665,'B','Google',0,1),(665,'C','Apple',0,2),(665,'D','Mozilla',0,3),
(666,'A','SFTP',1,0),(666,'B','SMTP',0,1),(666,'C','POP3',0,2),(666,'D','HTTP',0,3),
(667,'A','Kind regards',1,0),(667,'B','See ya',0,1),(667,'C','Bye',0,2),(667,'D','Catch you later',0,3),
(668,'A','Microsoft PowerPoint online',1,0),(668,'B','Excel',0,1),(668,'C','Word',0,2),(668,'D','Paint',0,3),
(669,'A','Digital commerce',1,0),(669,'B','Digital etiquette',0,1),(669,'C','Digital law',0,2),(669,'D','Digital literacy',0,3),

(670,'A','Two-factor authentication',1,0),(670,'B','Sharing passwords',0,1),(670,'C','Clicking unknown links',0,2),(670,'D','Ignoring updates',0,3),
(671,'A','Keeping screen at eye level',1,0),(671,'B','Slouching posture',0,1),(671,'C','Sleeping late',0,2),(671,'D','Ignoring breaks',0,3),
(672,'A','Opera Software',1,0),(672,'B','Google',0,1),(672,'C','Microsoft',0,2),(672,'D','Apple',0,3),
(673,'A','Using nicknames in formal emails',1,0),(673,'B','Clear subject line',0,1),(673,'C','Professional greeting',0,2),(673,'D','Checking grammar',0,3),
(674,'A','Evernote',1,0),(674,'B','WordPad',0,1),(674,'C','Excel',0,2),(674,'D','Paint',0,3);

INSERT INTO questions (id,school_id,author_id,type,sub_strand,topic,bloom_level,difficulty,year_group,marks,question_text,explanation,is_active) VALUES
(675,1,1,'mcq','S1/SS3','Internet and WWW',3,'hard',1,1,'Why is IPv6 considered more secure than IPv4?','IPv6 has built-in security features like IPsec.',1),
(676,1,1,'mcq','S1/SS3','Internet and WWW',3,'hard',1,1,'Why is DNS critical for internet use?','DNS translates human-readable names into IP addresses.',1),
(677,1,1,'mcq','S1/SS3','Internet and WWW',3,'hard',1,1,'Which part of a URL specifies the exact resource location?','The path specifies the resource location.',1),
(678,1,1,'mcq','S1/SS3','Internet and WWW',3,'hard',1,1,'Why is HTTPS preferred over HTTP?','HTTPS encrypts data for secure communication.',1),
(679,1,1,'mcq','S1/SS3','Internet and WWW',3,'hard',1,1,'Which search engine emphasizes anonymity and privacy?','DuckDuckGo emphasizes anonymity and privacy.',1),

(680,1,1,'mcq','S1/SS3','Email',3,'hard',1,1,'Why is phishing dangerous in emails?','Phishing tricks users into revealing sensitive information.',1),
(681,1,1,'mcq','S1/SS3','Email',3,'hard',1,1,'Why should attachments be scanned before opening?','Scanning prevents malware infections.',1),
(682,1,1,'mcq','S1/SS3','Email',3,'hard',1,1,'Why is professional tone important in emails?','Professional tone builds credibility and respect.',1),
(683,1,1,'mcq','S1/SS3','Email',3,'hard',1,1,'Which of these is a sign of a spam email?','Unsolicited offers are signs of spam.',1),
(684,1,1,'mcq','S1/SS3','Email',3,'hard',1,1,'Why should sensitive information not be sent via email?','Emails can be intercepted if not encrypted.',1),

(685,1,1,'mcq','S1/SS3','Online Communication',3,'hard',1,1,'Why is video conferencing better than email for urgent issues?','Video conferencing allows instant feedback.',1),
(686,1,1,'mcq','S1/SS3','Online Communication',3,'hard',1,1,'Why is social media not always reliable for news?','Social media may spread misinformation.',1),
(687,1,1,'mcq','S1/SS3','Online Communication',3,'hard',1,1,'Which tool is best for collaborative project management online?','Trello is best for project management.',1),
(688,1,1,'mcq','S1/SS3','Online Communication',3,'hard',1,1,'Why is instant messaging unsuitable for formal communication?','Instant messaging lacks formality and record-keeping.',1),
(689,1,1,'mcq','S1/SS3','Online Communication',3,'hard',1,1,'Which tool is best for academic webinars?','Zoom is best for academic webinars.',1),

(690,1,1,'mcq','S1/SS3','Netiquette and Digital Citizenship',3,'hard',1,1,'Why is digital law important?','Digital law ensures legal use of technology.',1),
(691,1,1,'mcq','S1/SS3','Netiquette and Digital Citizenship',3,'hard',1,1,'Why is digital literacy essential for students?','It helps students critically evaluate online information.',1),
(692,1,1,'mcq','S1/SS3','Netiquette and Digital Citizenship',3,'hard',1,1,'Why is respecting digital etiquette important in group chats?','It prevents misunderstandings and conflicts.',1),
(693,1,1,'mcq','S1/SS3','Netiquette and Digital Citizenship',3,'hard',1,1,'Why is digital access a challenge in rural areas?','Limited infrastructure reduces access.',1),
(694,1,1,'mcq','S1/SS3','Netiquette and Digital Citizenship',3,'hard',1,1,'Why is digital health important for young people?','It prevents negative effects of excessive screen use.',1),

(695,1,1,'mcq','S1/SS3','Online Safety',3,'hard',1,1,'Why is identity theft harmful?','It allows criminals to misuse personal data.',1),
(696,1,1,'mcq','S1/SS3','Online Safety',3,'hard',1,1,'Why is two-factor authentication safer than passwords alone?','It adds an extra layer of security.',1),
(697,1,1,'mcq','S1/SS3','Online Safety',3,'hard',1,1,'Why is malware dangerous?','Malware can damage files and steal data.',1),
(698,1,1,'mcq','S1/SS3','Online Safety',3,'hard',1,1,'Why is cyberbullying harmful to victims?','It causes emotional and psychological harm.',1),
(699,1,1,'mcq','S1/SS3','Online Safety',3,'hard',1,1,'Why is it important to verify websites before entering data?','Verification prevents phishing attacks.',1),

(700,1,1,'mcq','S1/SS3','Digital Health',3,'hard',1,1,'Why is screen time management important?','It prevents eye strain and mental fatigue.',1),
(701,1,1,'mcq','S1/SS3','Digital Health',3,'hard',1,1,'Why is posture important for long-term health?','Poor posture can cause chronic pain.',1),
(702,1,1,'mcq','S1/SS3','Digital Health',3,'hard',1,1,'Why is balancing online and offline life important?','Balance supports mental well-being.',1),
(703,1,1,'mcq','S1/SS3','Digital Health',3,'hard',1,1,'Why is sleep affected by screen use?','Screens emit blue light that disrupts sleep.',1),
(704,1,1,'mcq','S1/SS3','Digital Health',3,'hard',1,1,'Why is social media linked to anxiety?','Excessive use can cause comparison and stress.',1),

(705,1,1,'mcq','S1/SS3','Internet and WWW',4,'hard',1,1,'Why is ICANN important for internet governance?','ICANN coordinates domain names and IP addresses.',1),
(706,1,1,'mcq','S1/SS3','Internet and WWW',4,'hard',1,1,'Why is IPv6 adoption slow worldwide?','Compatibility issues slow IPv6 adoption.',1),
(707,1,1,'mcq','S1/SS3','Internet and WWW',4,'hard',1,1,'Why is caching important in browsers?','Caching speeds up loading of web pages.',1),
(708,1,1,'mcq','S1/SS3','Internet and WWW',4,'hard',1,1,'Why is encryption important in online communication?','Encryption protects data from hackers.',1),
(709,1,1,'mcq','S1/SS3','Internet and WWW',4,'hard',1,1,'Why is DNS sometimes targeted by hackers?','Hackers exploit DNS to redirect users.',1),

(710,1,1,'mcq','S1/SS3','Email',4,'hard',1,1,'Why is email encryption important?','Encryption protects sensitive information.',1),
(711,1,1,'mcq','S1/SS3','Online Communication',4,'hard',1,1,'Why is cloud collaboration important for businesses?','It allows real-time teamwork across locations.',1),
(712,1,1,'mcq','S1/SS3','Netiquette and Digital Citizenship',4,'hard',1,1,'Why is digital divide a global issue?','It creates inequality in access to technology.',1),
(713,1,1,'mcq','S1/SS3','Online Safety',4,'hard',1,1,'Why is regular software updating important?','Updates fix security vulnerabilities.',1),
(714,1,1,'mcq','S1/SS3','Digital Health',4,'hard',1,1,'Why is ergonomics important in ICT use?','Ergonomics prevents long-term health problems.',1);

INSERT INTO question_options (question_id,option_label,option_text,is_correct,sort_order) VALUES
(675,'A','IPv6 includes IPsec security',1,0),(675,'B','IPv4 automatically blocks spam emails',0,1),(675,'C','IPv6 addresses are shorter than IPv4',0,2),(675,'D','IPv4 prevents DNS errors',0,3),
(676,'A','Translates domain names to IP addresses',1,0),(676,'B','Stores browser history permanently',0,1),(676,'C','Provides antivirus protection',0,2),(676,'D','Creates new websites automatically',0,3),
(677,'A','Path',1,0),(677,'B','Protocol (http/https)',0,1),(677,'C','Top-level domain (.com, .org)',0,2),(677,'D','Bookmark title',0,3),
(678,'A','Encrypts communication for security',1,0),(678,'B','Reduces electricity consumption',0,1),(678,'C','Improves image resolution on websites',0,2),(678,'D','Eliminates the need for domain names',0,3),
(679,'A','DuckDuckGo',1,0),(679,'B','Google Search',0,1),(679,'C','Ask.com',0,2),(679,'D','Yahoo Search',0,3),

(680,'A','Tricks users into revealing sensitive info',1,0),(680,'B','Automatically filters junk mail',0,1),(680,'C','Provides free cloud storage',0,2),(680,'D','Improves email formatting styles',0,3),
(681,'A','Prevents malware infections',1,0),(681,'B','Speeds up internet connection',0,1),(681,'C','Checks spelling in the subject line',0,2),(681,'D','Encrypts only the sender’s name',0,3),
(682,'A','Builds credibility and respect',1,0),(682,'B','Guarantees faster delivery of emails',0,1),(682,'C','Adds decorative fonts automatically',0,2),(682,'D','Ensures unlimited storage space',0,3),
(683,'A','Unsolicited offers or promotions',1,0),(683,'B','Official government notices',0,1),(683,'C','Messages from verified teachers',0,2),(683,'D','School newsletters',0,3),
(684,'A','Emails can be intercepted if unencrypted',1,0),(684,'B','Emails cannot be sent internationally',0,1),(684,'C','Emails always delete themselves after 24 hours',0,2),(684,'D','Emails block viruses automatically',0,3);

INSERT INTO question_options (question_id,option_label,option_text,is_correct,sort_order) VALUES
(685,'A','Allows instant feedback',1,0),(685,'B','Delivers responses only after 24 hours',0,1),(685,'C','Requires physical presence in the office',0,2),(685,'D','Cannot share visual information',0,3),
(686,'A','May spread misinformation',1,0),(686,'B','Always peer-reviewed by scientists',0,1),(686,'C','Guaranteed to be unbiased',0,2),(686,'D','Published only by government agencies',0,3),
(687,'A','Trello',1,0),(687,'B','Paint (drawing software)',0,1),(687,'C','Windows Calculator',0,2),(687,'D','Offline diary notebook',0,3),
(688,'A','Lacks formality and record-keeping',1,0),(688,'B','Provides official legal contracts',0,1),(688,'C','Automatically archives for 10 years',0,2),(688,'D','Used exclusively for government notices',0,3),
(689,'A','Zoom',1,0),(689,'B','MS Word',0,1),(689,'C','Adobe Photoshop',0,2),(689,'D','Google Sheets',0,3),

(690,'A','Ensures legal use of technology',1,0),(690,'B','Provides free antivirus software',0,1),(690,'C','Improves handwriting speed',0,2),(690,'D','Blocks pop-up advertisements',0,3),
(691,'A','Helps students critically evaluate online info',1,0),(691,'B','Teaches cooking recipes online',0,1),(691,'C','Provides free sports coaching',0,2),(691,'D','Improves drawing skills',0,3),
(692,'A','Prevents misunderstandings and conflicts',1,0),(692,'B','Guarantees faster internet speed',0,1),(692,'C','Provides unlimited cloud storage',0,2),(692,'D','Automatically translates all languages',0,3),
(693,'A','Limited infrastructure reduces access',1,0),(693,'B','All rural areas have free Wi-Fi',0,1),(693,'C','Technology is banned by law',0,2),(693,'D','Devices are given free to everyone',0,3),
(694,'A','Prevents negative effects of excessive screen use',1,0),(694,'B','Provides unlimited free downloads',0,1),(694,'C','Improves cooking skills',0,2),(694,'D','Blocks all advertisements online',0,3);

INSERT INTO question_options (question_id,option_label,option_text,is_correct,sort_order) VALUES
(695,'A','Allows criminals to misuse personal data',1,0),(695,'B','Improves typing accuracy',0,1),(695,'C','Provides free electricity',0,2),(695,'D','Automatically updates software',0,3),
(696,'A','Adds an extra layer of security',1,0),(696,'B','Eliminates the need for passwords entirely',0,1),(696,'C','Provides faster internet browsing',0,2),(696,'D','Guarantees unlimited email storage',0,3),
(697,'A','Can damage files and steal data',1,0),(697,'B','Improves handwriting speed',0,1),(697,'C','Provides free cooking lessons',0,2),(697,'D','Automatically blocks advertisements',0,3),
(698,'A','Causes emotional and psychological harm',1,0),(698,'B','Provides free antivirus software',0,1),(698,'C','Improves cooking skills',0,2),(698,'D','Speeds up internet browsing',0,3),
(699,'A','Verification prevents phishing attacks',1,0),(699,'B','Guarantees free Wi-Fi access',0,1),(699,'C','Provides unlimited cloud storage',0,2),(699,'D','Automatically blocks spam emails',0,3),

(700,'A','Prevents eye strain and mental fatigue',1,0),(700,'B','Provides free antivirus software',0,1),(700,'C','Improves cooking skills',0,2),(700,'D','Speeds up downloads',0,3),
(701,'A','Poor posture can cause chronic pain',1,0),(701,'B','Provides free internet access',0,1),(701,'C','Improves handwriting speed',0,2),(701,'D','Blocks advertisements automatically',0,3),
(702,'A','Supports mental well-being',1,0),(702,'B','Provides unlimited cloud storage',0,1),(702,'C','Improves cooking skills',0,2),(702,'D','Speeds up browsing',0,3),
(703,'A','Blue light disrupts sleep patterns',1,0),(703,'B','Provides free antivirus software',0,1),(703,'C','Improves cooking skills',0,2),(703,'D','Blocks advertisements automatically',0,3),
(704,'A','Excessive use causes comparison and stress',1,0),(704,'B','Provides free electricity',0,1),(704,'C','Improves cooking skills',0,2),(704,'D','Speeds up downloads',0,3);

INSERT INTO question_options (question_id,option_label,option_text,is_correct,sort_order) VALUES
(705,'A','Coordinates domain names and IP addresses',1,0),(705,'B','Provides free antivirus software',0,1),(705,'C','Improves cooking skills',0,2),(705,'D','Blocks advertisements automatically',0,3),
(706,'A','Compatibility issues slow adoption',1,0),(706,'B','IPv6 requires no servers at all',0,1),(706,'C','IPv4 has more available addresses',0,2),(706,'D','IPv6 guarantees faster browsing',0,3),
(707,'A','Speeds up loading of web pages',1,0),(707,'B','Provides free antivirus software',0,1),(707,'C','Improves cooking skills',0,2),(707,'D','Blocks advertisements automatically',0,3),
(708,'A','Protects data from hackers',1,0),(708,'B','Provides free electricity',0,1),(708,'C','Improves cooking skills',0,2),(708,'D','Speeds up downloads',0,3),
(709,'A','Hackers exploit DNS to redirect users',1,0),(709,'B','DNS improves cooking skills',0,1),(709,'C','DNS speeds up downloads',0,2),(709,'D','DNS blocks advertisements automatically',0,3),

(710,'A','Encryption protects sensitive information',1,0),(710,'B','Encryption guarantees faster browsing',0,1),(710,'C','Encryption provides free Wi-Fi',0,2),(710,'D','Encryption blocks advertisements',0,3),
(711,'A','Allows real-time teamwork across locations',1,0),(711,'B','Provides free antivirus software',0,1),(711,'C','Improves cooking skills',0,2),(711,'D','Speeds up downloads',0,3),
(712,'A','Creates inequality in access to technology',1,0),(712,'B','Provides free internet everywhere',0,1),(712,'C','Improves cooking skills',0,2),(712,'D','Blocks advertisements automatically',0,3),
(713,'A','Updates fix security vulnerabilities',1,0),(713,'B','Updates provide free antivirus software',0,1),(713,'C','Updates improve cooking skills',0,2),(713,'D','Updates block advertisements',0,3),
(714,'A','Ergonomics prevents long-term health problems',1,0),(714,'B','Ergonomics guarantees faster browsing',0,1),(714,'C','Ergonomics provides free Wi-Fi',0,2),(714,'D','Ergonomics blocks advertisements',0,3);

SET FOREIGN_KEY_CHECKS=1;