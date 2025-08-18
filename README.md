# pdf2p2  

There was already a “PDF2Post” plugin, so we shortened and evolved it into **pdf2p2**.  

---

## Overview  

**pdf2p2** is a WordPress plugin for importing, processing, and publishing PDFs as posts. It automates the entire flow:   

- Parse an **RSS feed URL** to extract PDF links  
- Download and save PDFs into the **Media Library**  
- Create posts with:  
  - File name  
  - Original URL  
  - File attachment  
  - Hash for deduplication  
- Send the original PDF to **Mistral OCR** for text recognition  
- Populate post content with OCR results  
- Convert **Markdown → HTML → Gutenberg blocks**  
- Provide post-level metadata, accessible in the editor sidebar  
- Expose single posts as **JSON endpoints**  

---

## Demo & Testing  

- Try the [latest version](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/ManikinSaute/pdf2p2/main/blueprint.json) in WordPress Playground  
- Try the [latest stable version](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/ManikinSaute/pdf2p2/main/blueprint-stable.json) in WordPress Playground  
- Modify [pdf2p2.php](https://github.com/ManikinSaute/pdf2p2/blob/main/pdf2p2.php) locally and paste into the Playground file editor (then deactivate/reactivate to reload changes)  
- Edit the [blueprint.json](https://github.com/ManikinSaute/pdf2p2/blob/main/blueprint.json) directly  
- Test edits to a blueprint via the [Playground Builder](https://playground.wordpress.net/builder/builder.html)  
- Any commit to `main` containing **“zip-it”** will trigger:  
  - A ZIP build of the plugin  
  - An updated Playground link to run code from `main`  

---

## Features  

- Registers two custom post types:  
  - **Import CPT** → stores the original Markdown data  
  - **Gutenberg CPT** → stores the processed Gutenberg version  
- Provides a sidebar settings panel with:  
  - Dashboard  
  - Logging  
  - Settings  
  - Single import  
  - Bulk import  
  - RSS feed view  
- Registers a custom taxonomy for workflow states:  
  - **Unprocessed**  
  - **Processed**  
  - **Human verified**  
  - **Staff verified**  

---

## Roadmap  

- Provide access for **remote WordPress sites** to collect processed data  
- Add functionality to **remove old PDFs** while retaining metadata (hash, date, filename)  
- Index pages for:  
  - All available processes + staff-verified content  
  - All available processes + unverified content  

---

## Thanks  

Thanks for checking out **pdf2p2** 
