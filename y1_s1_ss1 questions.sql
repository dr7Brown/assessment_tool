-- ============================================================
-- AceICT: 100 MCQ Questions — Year 1, S1/SS1 Productivity Tools
-- Ghana GES ICT Curriculum
-- Sub-strand: SS1 — Productivity Tools
-- Topics: Word Processing, Spreadsheets, Presentations,
--         Email, File Management, Operating Systems
-- Difficulty: 40 Easy, 40 Medium, 20 Hard
-- All options stored as separate rows in question_options
-- school_id=1, author_id=1, year_group=1
-- ============================================================

SET FOREIGN_KEY_CHECKS=0;

-- ────────────────────────────────────────────────────────────
-- EASY QUESTIONS (1–40)
-- ────────────────────────────────────────────────────────────

INSERT INTO questions (id,school_id,author_id,type,sub_strand,topic,bloom_level,difficulty,year_group,marks,question_text,explanation,is_active) VALUES
(101,1,1,'mcq','S1/SS1','Word Processing','Remember','Easy',1,1,'Which Microsoft Word feature automatically corrects spelling mistakes as you type?','AutoCorrect automatically fixes common spelling mistakes and typos as you type in Microsoft Word.',1),
(102,1,1,'mcq','S1/SS1','Word Processing','Remember','Easy',1,1,'In Microsoft Word, which keyboard shortcut is used to SAVE a document?','Ctrl+S is the universal keyboard shortcut for saving a document in Microsoft Word and most Windows applications.',1),
(103,1,1,'mcq','S1/SS1','Word Processing','Remember','Easy',1,1,'Which menu in Microsoft Word contains the option to change the page orientation?','Page orientation (Portrait or Landscape) is found under the Layout or Page Layout menu in Microsoft Word.',1),
(104,1,1,'mcq','S1/SS1','Word Processing','Remember','Easy',1,1,'Ama wants to make selected text bold in Microsoft Word. Which keyboard shortcut should she use?','Ctrl+B toggles bold formatting on selected text in Microsoft Word.',1),
(105,1,1,'mcq','S1/SS1','Word Processing','Remember','Easy',1,1,'What is the default file extension for a Microsoft Word 2016 document?','.docx is the default file format for Microsoft Word documents from version 2007 onwards.',1),
(106,1,1,'mcq','S1/SS1','Word Processing','Remember','Easy',1,1,'Which Word Processing feature allows you to find and replace text throughout a document?','Find and Replace (Ctrl+H) allows users to locate specific text and replace it with different text throughout a document.',1),
(107,1,1,'mcq','S1/SS1','Word Processing','Remember','Easy',1,1,'Kofi accidentally deleted a paragraph. Which keyboard shortcut will restore it?','Ctrl+Z is the Undo shortcut that reverses the last action performed in Microsoft Word.',1),
(108,1,1,'mcq','S1/SS1','Word Processing','Remember','Easy',1,1,'Which of the following is NOT a text alignment option in Microsoft Word?','Diagonal is not a text alignment option. The four alignments are Left, Right, Centre, and Justify.',1),
(109,1,1,'mcq','S1/SS1','Word Processing','Understand','Easy',1,1,'What does the term "word wrap" mean in word processing?','Word wrap automatically moves text to the next line when it reaches the right margin, so the user does not need to press Enter at the end of each line.',1),
(110,1,1,'mcq','S1/SS1','Word Processing','Remember','Easy',1,1,'Which view in Microsoft Word shows exactly how a document will look when printed?','Print Layout view shows the document exactly as it will appear when printed, including margins, headers, and footers.',1),
(111,1,1,'mcq','S1/SS1','Spreadsheets','Remember','Easy',1,1,'What is the name of the small box at the intersection of a row and column in Microsoft Excel?','A cell is the basic unit of a spreadsheet, formed at the intersection of a row and a column.',1),
(112,1,1,'mcq','S1/SS1','Spreadsheets','Remember','Easy',1,1,'Which symbol must every Excel formula begin with?','All Excel formulas must begin with the equals sign (=) to tell Excel that what follows is a calculation.',1),
(113,1,1,'mcq','S1/SS1','Spreadsheets','Remember','Easy',1,1,'Abena wants to calculate the total of cells A1 to A10 in Excel. Which formula should she use?','=SUM(A1:A10) adds all values in the range from cell A1 to A10.',1),
(114,1,1,'mcq','S1/SS1','Spreadsheets','Remember','Easy',1,1,'In Excel, what does the cell reference "B3" mean?','B3 refers to the cell in Column B, Row 3. Columns are letters and rows are numbers.',1),
(115,1,1,'mcq','S1/SS1','Spreadsheets','Remember','Easy',1,1,'Which Excel function returns the HIGHEST value in a range of cells?','=MAX() returns the largest value in a specified range of cells.',1),
(116,1,1,'mcq','S1/SS1','Spreadsheets','Remember','Easy',1,1,'What does the Excel function =AVERAGE(B1:B5) calculate?','=AVERAGE() calculates the arithmetic mean (sum divided by count) of all values in the specified range.',1),
(117,1,1,'mcq','S1/SS1','Spreadsheets','Remember','Easy',1,1,'In Microsoft Excel, rows are identified by which of the following?','Rows in Excel are identified by numbers (1, 2, 3...) while columns are identified by letters (A, B, C...).',1),
(118,1,1,'mcq','S1/SS1','Spreadsheets','Remember','Easy',1,1,'Which keyboard shortcut opens a new workbook in Microsoft Excel?','Ctrl+N creates a new blank workbook in Microsoft Excel.',1),
(119,1,1,'mcq','S1/SS1','Spreadsheets','Understand','Easy',1,1,'What is a spreadsheet workbook?','A workbook is an Excel file that contains one or more worksheets (also called sheets or tabs).',1),
(120,1,1,'mcq','S1/SS1','Spreadsheets','Remember','Easy',1,1,'Kweku wants to count the number of cells containing numbers in Excel. Which function should he use?','=COUNT() counts the number of cells in a range that contain numerical values.',1),
(121,1,1,'mcq','S1/SS1','Presentations','Remember','Easy',1,1,'What is the correct term for a single page in a Microsoft PowerPoint presentation?','Each page in a PowerPoint presentation is called a slide. A presentation is made up of multiple slides.',1),
(122,1,1,'mcq','S1/SS1','Presentations','Remember','Easy',1,1,'Which keyboard shortcut starts a PowerPoint slideshow from the beginning?','F5 starts the PowerPoint slideshow from the first slide. Shift+F5 starts from the current slide.',1),
(123,1,1,'mcq','S1/SS1','Presentations','Remember','Easy',1,1,'In PowerPoint, what is a "slide layout"?','A slide layout is a pre-arranged set of placeholders for content such as titles, text, images, and charts.',1),
(124,1,1,'mcq','S1/SS1','Presentations','Remember','Easy',1,1,'Which PowerPoint view shows thumbnails of all slides on the left panel?','Normal view displays the slide editing area with a panel of slide thumbnails on the left.',1),
(125,1,1,'mcq','S1/SS1','Presentations','Remember','Easy',1,1,'What is the default file extension for a PowerPoint presentation?','.pptx is the default file format for Microsoft PowerPoint presentations from version 2007 onwards.',1),
(126,1,1,'mcq','S1/SS1','Presentations','Understand','Easy',1,1,'What is the purpose of Slide Transition in PowerPoint?','Slide transitions are visual effects that occur when moving from one slide to the next during a presentation.',1),
(127,1,1,'mcq','S1/SS1','Presentations','Remember','Easy',1,1,'Fatima wants to add a new slide to her PowerPoint. Which keyboard shortcut should she use?','Ctrl+M inserts a new slide in Microsoft PowerPoint.',1),
(128,1,1,'mcq','S1/SS1','Presentations','Remember','Easy',1,1,'Which PowerPoint feature allows text or objects to appear with movement effects on a slide?','Animation adds movement effects to individual objects on a slide, such as text flying in or fading.',1),
(129,1,1,'mcq','S1/SS1','Email','Remember','Easy',1,1,'What does "CC" stand for in an email?','CC stands for Carbon Copy. Recipients in the CC field receive a copy of the email and can see all other recipients.',1),
(130,1,1,'mcq','S1/SS1','Email','Remember','Easy',1,1,'Which part of an email address comes after the "@" symbol?','The domain name (e.g., gmail.com, yahoo.com) comes after the @ symbol in an email address.',1),
(131,1,1,'mcq','S1/SS1','Email','Remember','Easy',1,1,'What is an email attachment?','An email attachment is a file (document, image, video etc.) that is sent along with an email message.',1),
(132,1,1,'mcq','S1/SS1','Email','Remember','Easy',1,1,'What does "BCC" mean in email?','BCC stands for Blind Carbon Copy. Recipients in BCC receive the email but other recipients cannot see their addresses.',1),
(133,1,1,'mcq','S1/SS1','File Management','Remember','Easy',1,1,'What is the purpose of a folder in a computer file system?','Folders (also called directories) are used to organise and store related files together in a structured way.',1),
(134,1,1,'mcq','S1/SS1','File Management','Remember','Easy',1,1,'Which keyboard shortcut copies a selected file or text?','Ctrl+C copies the selected item to the clipboard without removing it from its original location.',1),
(135,1,1,'mcq','S1/SS1','File Management','Remember','Easy',1,1,'What happens to a file when you press Delete on a Windows computer?','On Windows, deleted files are moved to the Recycle Bin where they can be restored or permanently deleted.',1),
(136,1,1,'mcq','S1/SS1','File Management','Remember','Easy',1,1,'Which keyboard shortcut is used to paste copied content?','Ctrl+V pastes content from the clipboard to the current cursor position.',1),
(137,1,1,'mcq','S1/SS1','Operating Systems','Remember','Easy',1,1,'What is the main function of an Operating System?','An Operating System manages all hardware and software resources of a computer and provides services for programs.',1),
(138,1,1,'mcq','S1/SS1','Operating Systems','Remember','Easy',1,1,'Which of the following is an example of an Operating System?','Windows 10 is a popular Operating System developed by Microsoft. Others include macOS, Linux, and Android.',1),
(139,1,1,'mcq','S1/SS1','Operating Systems','Remember','Easy',1,1,'What is the Desktop in a Windows Operating System?','The Desktop is the main screen of a Windows computer that appears after login, showing icons and the taskbar.',1),
(140,1,1,'mcq','S1/SS1','Operating Systems','Remember','Easy',1,1,'Which bar appears at the bottom of the Windows screen and contains the Start button?','The Taskbar is the horizontal bar at the bottom of the Windows screen containing the Start button, open apps, and system clock.',1);

INSERT INTO questions (id,school_id,author_id,type,sub_strand,topic,bloom_level,difficulty,year_group,marks,question_text,explanation,is_active) VALUES
(141,1,1,'mcq','S1/SS1','Word Processing','Apply','Medium',1,1,'Nana is typing a school report in Microsoft Word. She wants every page to show her name at the top. Which feature should she use?','A Header appears at the top of every page automatically. It is found under the Insert menu in Microsoft Word.',1),
(142,1,1,'mcq','S1/SS1','Word Processing','Apply','Medium',1,1,'Kofi wants to prevent a paragraph heading from appearing alone at the bottom of a page. Which feature helps with this?','Widow/Orphan control (under Paragraph settings) prevents single lines from appearing isolated at the top or bottom of pages.',1),
(143,1,1,'mcq','S1/SS1','Word Processing','Understand','Medium',1,1,'What is the difference between "Save" and "Save As" in Microsoft Word?','"Save" updates the current file. "Save As" allows you to save a copy with a new name, location, or file format.',1),
(144,1,1,'mcq','S1/SS1','Word Processing','Apply','Medium',1,1,'Ama has written a 10-page report but wants to number the pages automatically. Which feature should she use?','Page Numbers (Insert > Page Number) automatically adds sequential page numbers to all pages of a document.',1),
(145,1,1,'mcq','S1/SS1','Word Processing','Understand','Medium',1,1,'What is a mail merge in Microsoft Word?','Mail merge combines a document template with a data source (like an Excel file) to create personalised letters or labels for multiple recipients.',1),
(146,1,1,'mcq','S1/SS1','Word Processing','Apply','Medium',1,1,'Abena needs to create a list of items with automatic numbering in Word. Which feature should she use?','Numbered Lists automatically number items sequentially and re-number them if items are added or removed.',1),
(147,1,1,'mcq','S1/SS1','Word Processing','Understand','Medium',1,1,'What is the purpose of the "Track Changes" feature in Microsoft Word?','Track Changes records all edits made to a document, showing additions, deletions, and formatting changes so reviewers can see modifications.',1),
(148,1,1,'mcq','S1/SS1','Word Processing','Apply','Medium',1,1,'Kweku wants to insert a table with 3 columns and 4 rows into his Word document. Which menu should he use?','Tables are inserted through the Insert menu in Microsoft Word. You can specify the number of rows and columns.',1),
(149,1,1,'mcq','S1/SS1','Word Processing','Apply','Medium',1,1,'A student wants to change the spacing between lines in her Word document from single to double spacing. Which menu option should she use?','Line spacing is adjusted through Format > Paragraph or the Home tab > Line and Paragraph Spacing button.',1),
(150,1,1,'mcq','S1/SS1','Word Processing','Understand','Medium',1,1,'What is the function of the Format Painter tool in Microsoft Word?','Format Painter copies formatting (font, size, colour, bold etc.) from one section of text and applies it to another section.',1),
(151,1,1,'mcq','S1/SS1','Spreadsheets','Apply','Medium',1,1,'Ama has sales data in Excel. Column A has item names and Column B has prices. She wants to find the average price. Which formula is correct?','=AVERAGE(B1:B10) calculates the mean of all price values in column B from row 1 to row 10.',1),
(152,1,1,'mcq','S1/SS1','Spreadsheets','Apply','Medium',1,1,'Kofi is creating a budget spreadsheet. He wants cell C1 to always show the value from cell A1 multiplied by cell B1. Which formula should he enter in C1?','=A1*B1 multiplies the values in cells A1 and B1 and displays the result in C1. The asterisk (*) is the multiplication operator.',1),
(153,1,1,'mcq','S1/SS1','Spreadsheets','Understand','Medium',1,1,'In Excel, what is the difference between a relative cell reference (A1) and an absolute cell reference ($A$1)?','A relative reference (A1) changes when copied to other cells. An absolute reference ($A$1) stays fixed regardless of where it is copied.',1),
(154,1,1,'mcq','S1/SS1','Spreadsheets','Apply','Medium',1,1,'Abena wants to display data in Excel as a bar chart. What must she do first?','You must first select the data range you want to chart before inserting a chart in Excel.',1),
(155,1,1,'mcq','S1/SS1','Spreadsheets','Apply','Medium',1,1,'A teacher has 30 students scores in Excel. She wants to count only students who scored above 50. Which function should she use?','=COUNTIF(range,">50") counts the number of cells in a range that meet a specified condition.',1),
(156,1,1,'mcq','S1/SS1','Spreadsheets','Understand','Medium',1,1,'What does "freezing panes" do in Microsoft Excel?','Freezing panes locks specific rows or columns so they remain visible when scrolling through a large spreadsheet.',1),
(157,1,1,'mcq','S1/SS1','Spreadsheets','Apply','Medium',1,1,'Kweku wants to sort a list of student names in Excel from A to Z. Which feature should he use?','The Sort feature (Data > Sort) arranges data in ascending (A-Z) or descending (Z-A) order.',1),
(158,1,1,'mcq','S1/SS1','Spreadsheets','Apply','Medium',1,1,'In Excel, Nana wants to display only students who scored above 80 from a long list. Which feature should she use?','AutoFilter (Data > Filter) allows you to show only rows that meet specific criteria while hiding other rows.',1),
(159,1,1,'mcq','S1/SS1','Spreadsheets','Understand','Medium',1,1,'What is a function in Microsoft Excel?','A function is a predefined formula in Excel that performs a specific calculation, such as SUM, AVERAGE, MAX, MIN, or COUNT.',1),
(160,1,1,'mcq','S1/SS1','Spreadsheets','Apply','Medium',1,1,'Ama wants to look up a student name in column A and return their score from column C. Which Excel function should she use?','VLOOKUP searches for a value in the first column of a range and returns a value from another column in the same row.',1),
(161,1,1,'mcq','S1/SS1','Presentations','Apply','Medium',1,1,'Kofi is presenting his school project. He wants to use consistent colours and fonts throughout all slides. What should he apply?','A Theme applies a consistent set of colours, fonts, and effects to all slides in a presentation at once.',1),
(162,1,1,'mcq','S1/SS1','Presentations','Apply','Medium',1,1,'Abena wants to hide a slide in her PowerPoint without deleting it. What should she do?','Right-clicking a slide and selecting "Hide Slide" makes it invisible during the slideshow without deleting it.',1),
(163,1,1,'mcq','S1/SS1','Presentations','Understand','Medium',1,1,'What is Slide Master in PowerPoint?','Slide Master is a template that controls the default formatting, layout, and design elements for all slides in a presentation.',1),
(164,1,1,'mcq','S1/SS1','Presentations','Apply','Medium',1,1,'Kweku wants to add a video from YouTube to his PowerPoint slide. What should he do?','Videos can be inserted into PowerPoint slides through Insert > Video. Online videos can be embedded using a link.',1),
(165,1,1,'mcq','S1/SS1','Presentations','Apply','Medium',1,1,'A student wants to set her PowerPoint slides to advance automatically every 5 seconds. Where should she find this option?','Automatic slide timing is set in the Transitions tab under "Advance Slide" > "After" and entering the time in seconds.',1),
(166,1,1,'mcq','S1/SS1','Email','Apply','Medium',1,1,'Fatima receives an email with a suspicious link asking her to enter her MTN MoMo PIN. What should she do?','Legitimate companies never ask for PINs or passwords by email. This is a phishing attempt and should be deleted and reported.',1),
(167,1,1,'mcq','S1/SS1','Email','Understand','Medium',1,1,'What is the difference between Reply and Reply All in email?','Reply sends your response only to the original sender. Reply All sends your response to the sender AND all other recipients.',1),
(168,1,1,'mcq','S1/SS1','Email','Apply','Medium',1,1,'Kofi wants to send the same newsletter to 500 customers without revealing their email addresses to each other. Which feature should he use?','BCC (Blind Carbon Copy) hides recipient addresses from each other, protecting privacy when sending mass emails.',1),
(169,1,1,'mcq','S1/SS1','Email','Understand','Medium',1,1,'What is an email signature?','An email signature is a block of text automatically added to the end of emails, typically containing the sender\'s name, title, and contact information.',1),
(170,1,1,'mcq','S1/SS1','File Management','Apply','Medium',1,1,'Ama has many files on her computer. She wants to quickly find a file called "assignment.docx". What should she use?','The Search function in Windows (Start menu search or File Explorer search) helps locate files by name quickly.',1),
(171,1,1,'mcq','S1/SS1','File Management','Understand','Medium',1,1,'What is the difference between Cut and Copy in Windows?','Cut removes the item from its original location and places it on the clipboard. Copy leaves the original and places a duplicate on the clipboard.',1),
(172,1,1,'mcq','S1/SS1','File Management','Apply','Medium',1,1,'Kweku accidentally emptied the Recycle Bin after deleting an important file. What is the best solution?','Once the Recycle Bin is emptied, files cannot be recovered through normal means. File recovery software may help but is not guaranteed.',1),
(173,1,1,'mcq','S1/SS1','File Management','Understand','Medium',1,1,'What is the purpose of file compression (zipping files)?','File compression reduces file size for easier storage and faster transfer, and can combine multiple files into one archive.',1),
(174,1,1,'mcq','S1/SS1','Operating Systems','Apply','Medium',1,1,'Abena\'s Windows computer is running slowly. She wants to close programs that are not responding. Which tool should she use?','Task Manager (Ctrl+Alt+Delete or Ctrl+Shift+Esc) shows all running processes and allows you to end unresponsive programs.',1),
(175,1,1,'mcq','S1/SS1','Operating Systems','Understand','Medium',1,1,'What is the purpose of the Control Panel in Windows?','Control Panel provides access to system settings including display, network, user accounts, hardware, and software configuration.',1),
(176,1,1,'mcq','S1/SS1','Operating Systems','Apply','Medium',1,1,'Kofi wants to take a screenshot of his computer screen in Windows. Which key should he press?','The Print Screen (PrtSc) key captures the entire screen. Alt+PrtSc captures only the active window.',1),
(177,1,1,'mcq','S1/SS1','Operating Systems','Understand','Medium',1,1,'What does it mean to "install" software on a computer?','Installing software copies the program files to the hard drive and configures the system so the software can be used.',1),
(178,1,1,'mcq','S1/SS1','Operating Systems','Apply','Medium',1,1,'Fatima sees a notification that Windows Update is available. What should she do?','Installing Windows updates is important as they include security patches, bug fixes, and performance improvements.',1),
(179,1,1,'mcq','S1/SS1','File Management','Understand','Medium',1,1,'What is cloud storage?','Cloud storage saves files on remote servers accessed via the internet (e.g. Google Drive, OneDrive, Dropbox) instead of on the local device.',1),
(180,1,1,'mcq','S1/SS1','Word Processing','Apply','Medium',1,1,'Nana\'s teacher asked her to submit her assignment with 1-inch margins on all sides. Where should she make this change in Word?','Margins are adjusted in the Layout (or Page Layout) tab under the Margins option in Microsoft Word.',1);

INSERT INTO questions (id,school_id,author_id,type,sub_strand,topic,bloom_level,difficulty,year_group,marks,question_text,explanation,is_active) VALUES
(181,1,1,'mcq','S1/SS1','Spreadsheets','Analyse','Hard',1,1,'Ama has a spreadsheet where column A contains student names and column B contains scores. She writes =IF(B2>=50,"Pass","Fail") in column C. What will appear in C2 if B2 contains 45?','The IF function checks the condition B2>=50. Since 45 is less than 50, the condition is FALSE and the function returns "Fail".',1),
(182,1,1,'mcq','S1/SS1','Spreadsheets','Analyse','Hard',1,1,'In Excel, Kofi has the formula =SUM($A$1:$A$10) in cell B1. When he copies this formula to cell C1, what happens?','The dollar signs ($) make both the column and row absolute. The formula stays =SUM($A$1:$A$10) in C1 — it does not change.',1),
(183,1,1,'mcq','S1/SS1','Spreadsheets','Analyse','Hard',1,1,'Abena uses the formula =VLOOKUP(D1,A1:B10,2,FALSE) in Excel. What does the "FALSE" argument mean?','FALSE specifies an exact match. VLOOKUP will only return a result if it finds the exact value from D1 in column A.',1),
(184,1,1,'mcq','S1/SS1','Spreadsheets','Analyse','Hard',1,1,'A school has three classes with scores in sheets named "Class1", "Class2", and "Class3". The formula =Class1!B2 in the summary sheet does what?','The exclamation mark (!) references a cell in another sheet. Class1!B2 retrieves the value from cell B2 in the sheet named Class1.',1),
(185,1,1,'mcq','S1/SS1','Word Processing','Analyse','Hard',1,1,'Kweku creates a Table of Contents in Word using the References tab. When he adds a new chapter and updates the TOC, what happens?','When you update a Table of Contents in Word (right-click > Update Field), it automatically reflects all heading changes and new page numbers.',1),
(186,1,1,'mcq','S1/SS1','Presentations','Analyse','Hard',1,1,'Nana is presenting to parents. Her PowerPoint file is 150MB because of high-resolution images. She needs to email it. What is the best solution?','Compressing images in PowerPoint (File > Compress Media or Format Picture > Compress) reduces file size while keeping acceptable quality.',1),
(187,1,1,'mcq','S1/SS1','Spreadsheets','Analyse','Hard',1,1,'A teacher uses =COUNTIF(B2:B31,">="&C1) in Excel where C1 contains the pass mark. A student changes the pass mark in C1 from 50 to 60. What happens to the COUNTIF result?','The formula references C1 dynamically. Changing C1 to 60 immediately updates the count to show only students who scored 60 or above.',1),
(188,1,1,'mcq','S1/SS1','File Management','Analyse','Hard',1,1,'Ama stores her school files on Google Drive. The internet is unavailable. Can she still access her files?','Google Drive files are stored in the cloud. Without internet, files are unavailable unless "Offline Access" was previously enabled for those files.',1),
(189,1,1,'mcq','S1/SS1','Operating Systems','Analyse','Hard',1,1,'Kofi notices his computer has 4GB RAM but runs slowly when many programs are open. What is most likely causing this?','RAM (Random Access Memory) is temporary working memory. Running many programs fills the RAM, causing the system to use slower virtual memory (page file), making the computer slow.',1),
(190,1,1,'mcq','S1/SS1','Word Processing','Analyse','Hard',1,1,'A student used Styles (Heading 1, Heading 2) throughout her Word document. Why is this more efficient than manually formatting each heading?','Using Styles allows global changes — editing Heading 1 style updates ALL headings at once. It also enables automatic Table of Contents generation and consistent formatting.',1),
(191,1,1,'mcq','S1/SS1','Spreadsheets','Analyse','Hard',1,1,'Abena has a formula =B2*C2 in cell D2. She drags the formula down to D10. What appears in cell D5?','When you drag a formula down, relative references adjust automatically. In D5, the formula becomes =B5*C5 — the row numbers increment to match.',1),
(192,1,1,'mcq','S1/SS1','Email','Analyse','Hard',1,1,'Kweku manages a school mailing list with 1000 addresses. He accidentally sends a private email to all 1000 recipients. What should he do immediately?','Send a follow-up apology email immediately acknowledging the error. Also review mailing list procedures to prevent recurrence.',1),
(193,1,1,'mcq','S1/SS1','Spreadsheets','Analyse','Hard',1,1,'A student writes =IF(AND(B2>50,C2>50),"Both Pass","Needs Improvement"). When does "Both Pass" appear?','AND() returns TRUE only when ALL conditions are true. Both B2>50 AND C2>50 must be true for "Both Pass" to display.',1),
(194,1,1,'mcq','S1/SS1','Operating Systems','Analyse','Hard',1,1,'Fatima\'s computer shows "Low Disk Space" warning. Her hard drive is 500GB and appears full. What should she do first?','Disk Cleanup (built into Windows) removes temporary files, system files, and empties the Recycle Bin to free up disk space quickly.',1),
(195,1,1,'mcq','S1/SS1','Word Processing','Analyse','Hard',1,1,'A student is working on a 50-page report in Word. When she changes the font of one Heading 1, only that heading changes. She wants ALL Heading 1 entries to change. What should she do?','Right-click the style in the Styles panel and select "Update Heading 1 to Match Selection" to apply the change to ALL instances of that style.',1),
(196,1,1,'mcq','S1/SS1','Spreadsheets','Analyse','Hard',1,1,'Nana\'s Excel spreadsheet calculates student grades. She wants to show "A" for 80+, "B" for 65-79, "C" for 50-64, and "F" below 50. Which approach is best?','Nested IF functions handle multiple conditions: =IF(B2>=80,"A",IF(B2>=65,"B",IF(B2>=50,"C","F"))). This checks conditions in order.',1),
(197,1,1,'mcq','S1/SS1','File Management','Analyse','Hard',1,1,'A school stores all student records in one shared folder with no subfolders. As files grow to thousands, what problem will occur and what is the solution?','Thousands of files in one folder makes locating files very slow and difficult. Creating a hierarchical subfolder structure (by year, class, subject) improves organisation and access speed.',1),
(198,1,1,'mcq','S1/SS1','Presentations','Analyse','Hard',1,1,'Kofi uses animations on every element of every slide in his PowerPoint presentation for parents. What problem might this cause?','Excessive animations distract from the content, slow down the presentation, and can appear unprofessional. Animations should be used sparingly and purposefully.',1),
(199,1,1,'mcq','S1/SS1','Operating Systems','Analyse','Hard',1,1,'Abena receives a pop-up saying "Your computer has a virus! Call this number immediately!" What should she do?','This is a scareware/tech support scam. Legitimate antivirus software does not ask you to call phone numbers. Close the browser and run a trusted antivirus scan.',1),
(200,1,1,'mcq','S1/SS1','Spreadsheets','Analyse','Hard',1,1,'A student creates an Excel chart but the data labels show wrong values after she added new data. What should she do?','Right-clicking the chart and selecting "Select Data" or "Refresh" updates the chart to include the newly added data range.',1);

-- ────────────────────────────────────────────────────────────
-- QUESTION OPTIONS
-- ────────────────────────────────────────────────────────────

INSERT INTO question_options (question_id,option_label,option_text,is_correct,sort_order) VALUES
-- Q101
(101,'A','AutoFormat',0,0),(101,'B','AutoCorrect',1,1),(101,'C','Spell Check',0,2),(101,'D','Grammar Check',0,3),
-- Q102
(102,'A','Ctrl+P',0,0),(102,'B','Ctrl+C',0,1),(102,'C','Ctrl+S',1,2),(102,'D','Ctrl+Z',0,3),
-- Q103
(103,'A','Insert',0,0),(103,'B','Home',0,1),(103,'C','View',0,2),(103,'D','Layout',1,3),
-- Q104
(104,'A','Ctrl+I',0,0),(104,'B','Ctrl+B',1,1),(104,'C','Ctrl+U',0,2),(104,'D','Ctrl+D',0,3),
-- Q105
(105,'A','.doc',0,0),(105,'B','.txt',0,1),(105,'C','.pdf',0,2),(105,'D','.docx',1,3),
-- Q106
(106,'A','Spell Check',0,0),(106,'B','AutoCorrect',0,1),(106,'C','Find and Replace',1,2),(106,'D','Track Changes',0,3),
-- Q107
(107,'A','Ctrl+Y',0,0),(107,'B','Ctrl+X',0,1),(107,'C','Ctrl+Z',1,2),(107,'D','Ctrl+D',0,3),
-- Q108
(108,'A','Left',0,0),(108,'B','Right',0,1),(108,'C','Diagonal',1,2),(108,'D','Justify',0,3),
-- Q109
(109,'A','Checking words for spelling errors automatically',0,0),(109,'B','Moving text to the next line automatically when the margin is reached',1,1),(109,'C','Wrapping images around text',0,2),(109,'D','Highlighting words in the document',0,3),
-- Q110
(110,'A','Draft view',0,0),(110,'B','Outline view',0,1),(110,'C','Web Layout view',0,2),(110,'D','Print Layout view',1,3),
-- Q111
(111,'A','Row',0,0),(111,'B','Column',0,1),(111,'C','Cell',1,2),(111,'D','Range',0,3),
-- Q112
(112,'A','@',0,0),(112,'B','#',0,1),(112,'C','=',1,2),(112,'D','+',0,3),
-- Q113
(113,'A','=ADD(A1,A10)',0,0),(113,'B','=TOTAL(A1:A10)',0,1),(113,'C','=SUM(A1:A10)',1,2),(113,'D','=COUNT(A1:A10)',0,3),
-- Q114
(114,'A','Column B, Row 3',1,0),(114,'B','Row B, Column 3',0,1),(114,'C','Sheet B, Row 3',0,2),(114,'D','Block B, Section 3',0,3),
-- Q115
(115,'A','=HIGH()',0,0),(115,'B','=LARGE()',0,1),(115,'C','=TOP()',0,2),(115,'D','=MAX()',1,3),
-- Q116
(116,'A','Adds all values in B1 to B5',0,0),(116,'B','Counts the number of values in B1 to B5',0,1),(116,'C','Calculates the mean of values in B1 to B5',1,2),(116,'D','Finds the highest value in B1 to B5',0,3),
-- Q117
(117,'A','Letters',0,0),(117,'B','Numbers',1,1),(117,'C','Symbols',0,2),(117,'D','Colours',0,3),
-- Q118
(118,'A','Ctrl+O',0,0),(118,'B','Ctrl+W',0,1),(118,'C','Ctrl+N',1,2),(118,'D','Ctrl+F',0,3),
-- Q119
(119,'A','A single cell in a spreadsheet',0,0),(119,'B','A single row of data',0,1),(119,'C','A file containing one or more worksheets',1,2),(119,'D','A chart embedded in a spreadsheet',0,3),
-- Q120
(120,'A','=SUM()',0,0),(120,'B','=AVERAGE()',0,1),(120,'C','=COUNT()',1,2),(120,'D','=NUMBER()',0,3),
-- Q121
(121,'A','Page',0,0),(121,'B','Sheet',0,1),(121,'C','Slide',1,2),(121,'D','Frame',0,3),
-- Q122
(122,'A','Ctrl+P',0,0),(122,'B','F5',1,1),(122,'C','F1',0,2),(122,'D','Ctrl+S',0,3),
-- Q123
(123,'A','A background image for slides',0,0),(123,'B','A pre-arranged set of placeholders for content on a slide',1,1),(123,'C','A colour scheme for the presentation',0,2),(123,'D','A type of slide transition effect',0,3),
-- Q124
(124,'A','Slide Sorter view',0,0),(124,'B','Reading view',0,1),(124,'C','Normal view',1,2),(124,'D','Presenter view',0,3),
-- Q125
(125,'A','.ppt',0,0),(125,'B','.ppx',0,1),(125,'C','.pdf',0,2),(125,'D','.pptx',1,3),
-- Q126
(126,'A','Effects applied to individual objects on a slide',0,0),(126,'B','Visual effects that occur when moving from one slide to the next',1,1),(126,'C','A way to hide selected slides',0,2),(126,'D','A method to add audio to slides',0,3),
-- Q127
(127,'A','Ctrl+N',0,0),(127,'B','Ctrl+S',0,1),(127,'C','Ctrl+M',1,2),(127,'D','Ctrl+A',0,3),
-- Q128
(128,'A','Transition',0,0),(128,'B','Animation',1,1),(128,'C','Theme',0,2),(128,'D','Layout',0,3),
-- Q129
(129,'A','Copied Content',0,0),(129,'B','Carbon Copy',1,1),(129,'C','Customer Contact',0,2),(129,'D','Certified Correspondence',0,3),
-- Q130
(130,'A','The username',0,0),(130,'B','The country code',0,1),(130,'C','The domain name',1,2),(130,'D','The email subject',0,3),
-- Q131
(131,'A','A separate email account linked to the main one',0,0),(131,'B','The subject line of an email',0,1),(131,'C','A file sent along with an email message',1,2),(131,'D','A saved draft email',0,3),
-- Q132
(132,'A','Broadcast Carbon Copy',0,0),(132,'B','Basic Carbon Copy',0,1),(132,'C','Blind Carbon Copy',1,2),(132,'D','Block Carbon Copy',0,3),
-- Q133
(133,'A','To speed up the computer processor',0,0),(133,'B','To connect to the internet',0,1),(133,'C','To organise and store related files together',1,2),(133,'D','To protect files from viruses',0,3),
-- Q134
(134,'A','Ctrl+X',0,0),(134,'B','Ctrl+V',0,1),(134,'C','Ctrl+Z',0,2),(134,'D','Ctrl+C',1,3),
-- Q135
(135,'A','It is permanently deleted immediately',0,0),(135,'B','It is moved to the Recycle Bin',1,1),(135,'C','It is saved to a backup folder',0,2),(135,'D','It is compressed automatically',0,3),
-- Q136
(136,'A','Ctrl+C',0,0),(136,'B','Ctrl+X',0,1),(136,'C','Ctrl+Z',0,2),(136,'D','Ctrl+V',1,3),
-- Q137
(137,'A','To display graphics on screen',0,0),(137,'B','To manage all hardware and software resources and provide services for programs',1,1),(137,'C','To connect the computer to the internet',0,2),(137,'D','To store data permanently',0,3),
-- Q138
(138,'A','Microsoft Word',0,0),(138,'B','Google Chrome',0,1),(138,'C','Windows 10',1,2),(138,'D','VLC Media Player',0,3),
-- Q139
(139,'A','The inside of the computer case',0,0),(139,'B','The main screen showing icons and taskbar after login',1,1),(139,'C','The screen shown during startup',0,2),(139,'D','The control panel settings screen',0,3),
-- Q140
(140,'A','Menu Bar',0,0),(140,'B','Status Bar',0,1),(140,'C','Ribbon',0,2),(140,'D','Taskbar',1,3);

INSERT INTO question_options (question_id,option_label,option_text,is_correct,sort_order) VALUES
-- Q141
(141,'A','Footnote',0,0),(141,'B','Bookmark',0,1),(141,'C','Header',1,2),(141,'D','Watermark',0,3),
-- Q142
(142,'A','Page Break',0,0),(142,'B','Column Break',0,1),(142,'C','Widow/Orphan Control',1,2),(142,'D','Section Break',0,3),
-- Q143
(143,'A','Save updates the file; Save As creates a copy with a new name or format',1,0),(143,'B','They are exactly the same',0,1),(143,'C','Save As is for images only',0,2),(143,'D','Save closes the document automatically',0,3),
-- Q144
(144,'A','Watermark',0,0),(144,'B','Page Numbers',1,1),(144,'C','Header',0,2),(144,'D','Footnote',0,3),
-- Q145
(145,'A','Merging two Word documents into one',0,0),(145,'B','Combining a document template with a data source to create personalised letters for multiple recipients',1,1),(145,'C','A way to track changes made by multiple users',0,2),(145,'D','Merging all pages into a PDF',0,3),
-- Q146
(146,'A','Bullet list',0,0),(146,'B','Numbered list',1,1),(146,'C','Outline view',0,2),(146,'D','SmartArt',0,3),
-- Q147
(147,'A','It automatically corrects spelling errors',0,0),(147,'B','It records all edits so reviewers can see changes made',1,1),(147,'C','It tracks how long the document has been open',0,2),(147,'D','It saves previous versions automatically',0,3),
-- Q148
(148,'A','Home',0,0),(148,'B','View',0,1),(148,'C','Insert',1,2),(148,'D','References',0,3),
-- Q149
(149,'A','Insert > Line Spacing',0,0),(149,'B','Format > Paragraph or Home > Line and Paragraph Spacing',1,1),(149,'C','View > Spacing',0,2),(149,'D','Page Layout > Spacing',0,3),
-- Q150
(150,'A','It formats the page background',0,0),(150,'B','It copies formatting from one section and applies it to another',1,1),(150,'C','It converts text to a table',0,2),(150,'D','It adds a border around selected text',0,3),
-- Q151
(151,'A','=SUM(B1:B10)',0,0),(151,'B','=AVERAGE(B1:B10)',1,1),(151,'C','=MEAN(B1:B10)',0,2),(151,'D','=TOTAL(B1:B10)/10',0,3),
-- Q152
(152,'A','=A1+B1',0,0),(152,'B','=A1xB1',0,1),(152,'C','=A1*B1',1,2),(152,'D','=MULTIPLY(A1,B1)',0,3),
-- Q153
(153,'A','They are the same — both stay fixed when copied',0,0),(153,'B','Relative reference changes when copied; absolute reference stays fixed',1,1),(153,'C','Absolute references are used only in charts',0,2),(153,'D','Relative references cannot be used in formulas',0,3),
-- Q154
(154,'A','Open the Insert tab immediately',0,0),(154,'B','Select the data range to be charted first',1,1),(154,'C','Go to File > Create Chart',0,2),(154,'D','Type the chart values manually',0,3),
-- Q155
(155,'A','=IF(B2:B31>50,"Pass","Fail")',0,0),(155,'B','=COUNT(B2:B31,">50")',0,1),(155,'C','=COUNTIF(B2:B31,">50")',1,2),(155,'D','=SUMIF(B2:B31,">50")',0,3),
-- Q156
(156,'A','It merges selected cells permanently',0,0),(156,'B','It locks specific rows or columns to remain visible while scrolling',1,1),(156,'C','It prevents other users from editing the spreadsheet',0,2),(156,'D','It saves the spreadsheet automatically',0,3),
-- Q157
(157,'A','Format > Arrange',0,0),(157,'B','Home > Order',0,1),(157,'C','Data > Sort',1,2),(157,'D','Insert > Sort',0,3),
-- Q158
(158,'A','Conditional Formatting',0,0),(158,'B','Data Validation',0,1),(158,'C','AutoFilter',1,2),(158,'D','VLOOKUP',0,3),
-- Q159
(159,'A','A type of chart in Excel',0,0),(159,'B','A predefined formula that performs a specific calculation',1,1),(159,'C','A way to format cells with colours',0,2),(159,'D','A method for sorting data alphabetically',0,3),
-- Q160
(160,'A','HLOOKUP',0,0),(160,'B','INDEX',0,1),(160,'C','MATCH',0,2),(160,'D','VLOOKUP',1,3),
-- Q161
(161,'A','Slide Layout',0,0),(161,'B','Animation',0,1),(161,'C','Theme',1,2),(161,'D','Slide Master',0,3),
-- Q162
(162,'A','Delete the slide and recreate it later',0,0),(162,'B','Move it to a separate presentation file',0,1),(162,'C','Right-click and select Hide Slide',1,2),(162,'D','Change the slide colour to white',0,3),
-- Q163
(163,'A','A collection of slide transition effects',0,0),(163,'B','A template controlling default formatting for all slides',1,1),(163,'C','A backup copy of the presentation',0,2),(163,'D','A view mode for presenting to an audience',0,3),
-- Q164
(164,'A','Copy the video file directly into the slide',0,0),(164,'B','Use Insert > Video and embed using the YouTube link',1,1),(164,'C','Type the YouTube URL as text on the slide',0,2),(164,'D','Screenshots of the video must be used instead',0,3),
-- Q165
(165,'A','Animation tab > Timing',0,0),(165,'B','Slide Show tab > Set Up Show',0,1),(165,'C','Transitions tab > Advance Slide > After',1,2),(165,'D','Insert tab > Timer',0,3),
-- Q166
(166,'A','Click the link and enter the PIN to verify the account',0,0),(166,'B','Forward the email to friends to warn them',0,1),(166,'C','Reply asking for more information',0,2),(166,'D','Delete the email without clicking the link and report it as phishing',1,3),
-- Q167
(167,'A','Reply sends to everyone; Reply All sends only to the sender',0,0),(167,'B','Reply sends only to the sender; Reply All sends to the sender and all recipients',1,1),(167,'C','They are exactly the same function',0,2),(167,'D','Reply All forwards the email to new recipients',0,3),
-- Q168
(168,'A','CC field',0,0),(168,'B','Reply All',0,1),(168,'C','BCC field',1,2),(168,'D','Forward',0,3),
-- Q169
(169,'A','A template for composing new emails',0,0),(169,'B','An auto-reply message sent when you are away',0,1),(169,'C','A block of text automatically added to the end of sent emails',1,2),(169,'D','A folder for storing sent messages',0,3),
-- Q170
(170,'A','Open every folder manually one by one',0,0),(170,'B','Use the Search function in Windows',1,1),(170,'C','Restart the computer to refresh the file list',0,2),(170,'D','Check the Recycle Bin',0,3),
-- Q171
(171,'A','Cut and Copy do the same thing',0,0),(171,'B','Cut removes from original location; Copy leaves original and creates a duplicate',1,1),(171,'C','Copy removes from original; Cut creates a duplicate',0,2),(171,'D','Cut is for text only; Copy is for files only',0,3),
-- Q172
(172,'A','The file can be recovered from the Recycle Bin',0,0),(172,'B','Press Ctrl+Z to undo emptying the Recycle Bin',0,1),(172,'C','The file is automatically backed up to OneDrive',0,2),(172,'D','Use file recovery software — normal recovery is not possible',1,3),
-- Q173
(173,'A','To encrypt files for security',0,0),(173,'B','To reduce file size for easier storage and transfer',1,1),(173,'C','To permanently delete files',0,2),(173,'D','To convert files to a different format',0,3),
-- Q174
(174,'A','Control Panel',0,0),(174,'B','Device Manager',0,1),(174,'C','Task Manager',1,2),(174,'D','File Explorer',0,3),
-- Q175
(175,'A','It is used to browse the internet',0,0),(175,'B','It provides access to system settings and configuration',1,1),(175,'C','It is used to create documents',0,2),(175,'D','It manages email accounts',0,3),
-- Q176
(176,'A','Ctrl+Alt+Delete',0,0),(176,'B','Print Screen (PrtSc)',1,1),(176,'C','Windows key + D',0,2),(176,'D','F12',0,3),
-- Q177
(177,'A','Downloading a file from the internet',0,0),(177,'B','Copying program files to the hard drive and configuring the system to run the software',1,1),(177,'C','Creating a shortcut on the Desktop',0,2),(177,'D','Updating an existing program',0,3),
-- Q178
(178,'A','Ignore the notification to avoid slowing the computer',0,0),(178,'B','Wait until the computer shows errors before updating',0,1),(178,'C','Install the update as it contains important security patches and improvements',1,2),(178,'D','Uninstall Windows and reinstall it',0,3),
-- Q179
(179,'A','Storage on a USB flash drive',0,0),(179,'B','Storage on the computer hard drive',0,1),(179,'C','Storage on remote servers accessed via the internet',1,2),(179,'D','Storage on a CD or DVD',0,3),
-- Q180
(180,'A','Home tab > Margins',0,0),(180,'B','Insert tab > Margins',0,1),(180,'C','View tab > Page Setup',0,2),(180,'D','Layout tab > Margins',1,3);

INSERT INTO question_options (question_id,option_label,option_text,is_correct,sort_order) VALUES
-- Q181
(181,'A','Pass',0,0),(181,'B','Fail',1,1),(181,'C','45',0,2),(181,'D','TRUE',0,3),
-- Q182
(182,'A','It changes to =SUM($C$1:$C$10)',0,0),(182,'B','It changes to =SUM(B1:B10)',0,1),(182,'C','It stays as =SUM($A$1:$A$10)',1,2),(182,'D','It shows a #REF! error',0,3),
-- Q183
(183,'A','The lookup returns approximate matches',0,0),(183,'B','The lookup searches from bottom to top',0,1),(183,'C','The lookup finds only exact matches',1,2),(183,'D','The lookup ignores blank cells',0,3),
-- Q184
(184,'A','Saves the value from B2 into Class1',0,0),(184,'B','Retrieves the value from cell B2 in the sheet named Class1',1,1),(184,'C','Creates a link to an external file called Class1',0,2),(184,'D','Copies the sheet named Class1 into cell B2',0,3),
-- Q185
(185,'A','The TOC disappears completely',0,0),(185,'B','The new chapter is added but page numbers do not update',0,1),(185,'C','The TOC updates automatically showing new headings and correct page numbers',1,2),(185,'D','Word asks you to manually type the new chapter title',0,3),
-- Q186
(186,'A','Delete some slides to reduce the file size',0,0),(186,'B','Convert the file to PDF before emailing',0,1),(186,'C','Compress the images in PowerPoint to reduce file size',1,2),(186,'D','Remove all animations and transitions',0,3),
-- Q187
(187,'A','Nothing changes — the formula is fixed',0,0),(187,'B','The formula shows an error',0,1),(187,'C','The count updates to show students who scored 60 or above',1,2),(187,'D','The formula deletes the old count',0,3),
-- Q188
(188,'A','Yes — Google Drive stores copies on the device automatically',0,0),(188,'B','Yes — cloud files are always available offline',0,1),(188,'C','No — cloud files require internet unless offline access was enabled',1,2),(188,'D','No — Google Drive deletes files after 24 hours offline',0,3),
-- Q189
(189,'A','The processor is too old',0,0),(189,'B','The hard drive is failing',0,1),(189,'C','Running many programs fills RAM causing the system to use slower virtual memory',1,2),(189,'D','The monitor resolution is too high',0,3),
-- Q190
(190,'A','No benefit — manual formatting is just as efficient',0,0),(190,'B','Styles allow global changes to all headings at once and enable automatic Table of Contents',1,1),(190,'C','Styles only work in documents shorter than 10 pages',0,2),(190,'D','Styles cannot be customised for school reports',0,3),
-- Q191
(191,'A','=B2*C2',0,0),(191,'B','=B3*C3',0,1),(191,'C','=B5*C5',1,2),(191,'D','=B10*C10',0,3),
-- Q192
(192,'A','Delete all email records to hide the mistake',0,0),(192,'B','Blame the technical system for the error',0,1),(192,'C','Send a follow-up apology email and review mailing list procedures',1,2),(192,'D','Ignore it — recipients will understand',0,3),
-- Q193
(193,'A','When either B2>50 or C2>50',0,0),(193,'B','When neither B2 nor C2 is above 50',0,1),(193,'C','When both B2>50 AND C2>50',1,2),(193,'D','When the sum of B2 and C2 is greater than 100',0,3),
-- Q194
(194,'A','Buy a new computer immediately',0,0),(194,'B','Run Disk Cleanup to remove temporary and unnecessary files first',1,1),(194,'C','Delete the Windows folder to free space',0,2),(194,'D','Reduce screen brightness to save space',0,3),
-- Q195
(195,'A','Use Ctrl+H to find and replace all headings manually',0,0),(195,'B','Delete all headings and retype them in the new font',0,1),(195,'C','Right-click the style and select Update Heading 1 to Match Selection',1,2),(195,'D','Change the document theme instead',0,3),
-- Q196
(196,'A','Use four separate IF formulas in four columns',0,0),(196,'B','Use nested IF functions: =IF(B2>=80,"A",IF(B2>=65,"B",IF(B2>=50,"C","F")))',1,1),(196,'C','Use VLOOKUP with a separate grade table',0,2),(196,'D','Manually enter grades for each student',0,3),
-- Q197
(197,'A','No problem — computers can handle unlimited files in one folder',0,0),(197,'B','Files will be automatically sorted by the OS',0,1),(197,'C','Finding files becomes very slow — create a hierarchical subfolder structure',1,2),(197,'D','The computer will automatically delete old files',0,3),
-- Q198
(198,'A','The presentation file becomes too large to open',0,0),(198,'B','Excessive animations distract from content and appear unprofessional',1,1),(198,'C','Animations prevent the slides from printing',0,2),(198,'D','Too many animations crash PowerPoint',0,3),
-- Q199
(199,'A','Call the number immediately to get technical support',0,0),(199,'B','Click OK on the pop-up to remove the virus',0,1),(199,'C','Pay the fee shown to unlock the computer',0,2),(199,'D','Close the browser and run a trusted antivirus scan — it is a scam',1,3),
-- Q200
(200,'A','Delete the chart and create a new one from scratch',0,0),(200,'B','Right-click the chart and select Select Data or Refresh to update it',1,1),(200,'C','Copy the new data and paste it directly onto the chart',0,2),(200,'D','Change the chart type to fix the labels',0,3);

SET FOREIGN_KEY_CHECKS=1;
