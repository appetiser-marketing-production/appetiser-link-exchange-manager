# Appetiser Link Exchange Manager

## 📘 Introduction

The **Appetiser Link Exchange Manager** plugin is built to streamline and centralize our link exchange efforts.  
Designed for our content managers, it enables easy insertion, tracking, and management of outbound links within our blog content — no need to manually edit individual posts.

---

## 🎯 Purpose and Benefits

- **Efficiency**: Manage link exchanges in one dashboard, reducing manual effort and avoiding missed placements.
- **Inventory Control**: Serves as a living list of all outbound links deployed for partnerships and SEO efforts.
- **Fast Deployment**: New link exchanges can go live instantly by simply adding them to the dashboard.
- **Quality Control**: Avoids duplicate placements, ensures links appear only where intended, and supports easy toggling.
- **Scalable**: Efficiently manage link exchanges across hundreds of blog posts.

---

## 🚀 What Can the Plugin Do?

- Provides a centralized dashboard to manage outbound links used for link exchange or partnerships.
- Allows users to map a specific keyword or phrase to an outbound link on a specific blog post.
- Dynamically inserts links into content during page rendering — no need to edit the post manually.
- Supports **enable/disable** toggles for each mapping, giving content managers full control over active links.
- Includes **real-time validation** to ensure mappings only point to existing blog posts.
- Offers a **CSV export** feature to download all configured link mappings for review, backup, or reporting.

---

## ⚠️ Limitations

- Does **not** modify or overwrite existing links manually added to blog content.
- Only supports **blog posts** (`post` post type); **pages and custom post types are excluded**.
- Outbound links are only inserted into the **specific post URL** defined in the mapping — not site-wide.
- Link insertion happens at **render time**; it is not permanently saved to the post content.

---

## 🛠️ How to Use the Plugin

1. Go to **Tools > Link Exchange Manager** in the WordPress admin.
2. Add a new mapping group:
   - **Blog Post URL** – The internal URL where the link should be inserted.
   - **Keyword(s)** – The exact word or phrase to be linked.
   - **Outbound Link** – The external destination.
   - **Enable** – Use the toggle to activate or disable the mapping.
3. Click **Save Mappings** to apply changes.

Once saved, the keyword will automatically be linked across the specified post — without affecting headings or existing links.

