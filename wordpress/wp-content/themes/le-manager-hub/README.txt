LE MANAGER HUB — ALL-IN-ONE WORDPRESS THEME 1.1.0

INSTALL
1. In WordPress, open Appearance > Themes > Add New > Upload Theme.
2. Upload le-manager-hub-all-in-one-1.1.0.zip, click Install Now, then Activate.
3. If the old Le Manager Hub theme is installed, use WordPress's replacement option.
4. Deactivate the separate Le Manager Hub Core plugin if previously installed.
   The theme suppresses its known callbacks while active to prevent duplication.
5. Open Appearance > Le Manager Hub for management instructions.
6. To accept new members, enable Settings > General > Membership > Anyone can register.
   Registration remains closed if this WordPress setting is off.
7. Publish content through Artists, Professionals, Companies, Events, Opportunities,
   TV, Radio and Podcasts. Add featured images, excerpts, taxonomy terms and the
   Le Manager Hub Details fields. Published records appear on the connected directory and archive pages.
8. Manage private inquiries through Bookings. Manage member levels and Event Squad
   participation through Users > Edit User. Levels are assigned manually.

One theme folder, no separate Core plugin and no build command required.
Requires WordPress 6.5+ and PHP 8.0+. Validated locally with WP 7.1.2 / PHP 8.3.35.
The SQLite adapter used in local tests is not included or required in this ZIP.

INCLUDED
Exact V5 black/white/red design and responsive rules; homepage; public archives and detail
pages; searchable directory; fan/professional/company registration; WordPress login,
logout and password recovery; account page; follow/unfollow; private booking inquiry
form and history; Event Squad opt-in/out; manually managed fan levels; media playback
and provider embeds; all nine original content types, five taxonomies and metadata;
WordPress administrative management.

TV accepts video links (MP4/WebM, WordPress-supported embeds or an external watch
link). Radio and Podcast accept browser-playable audio/stream URLs. Provide your
own media and published content. The original external Unsplash hero is retained.

DATA COMPATIBILITY
Preserves original lmh_* post types, roles, taxonomies, _lmh_* post metadata and
lmh_following user metadata. Installation does not import dummy listings, delete
records, change the site title, enable public signup or replace WordPress settings.
Switching to another theme retains records but disables the embedded Hub features.
Reactivate this theme to restore them. Back up an existing site before replacing a theme.

REST API (relative to /wp-json/lmh/v1/; plain permalinks also supported)
POST register — name, email, password (10+ characters), account_type
GET directory — type (lmh_artist/lmh_professional/lmh_company), search, page, per_page
GET me — authenticated member and following IDs
POST follow/{artist_id} — toggle following a published artist
POST bookings — talent_id, event_date (YYYY-MM-DD), budget, message, optional event_id
GET bookings/mine — current member's latest 50 inquiries
POST squad — joined (boolean)
Authenticated browser requests require WordPress login cookies and a REST nonce.
Bookings have no generic public wp/v2 route and are administered by administrators.
Existing public content REST routes remain available under wp/v2.

SOURCE AND EXACT HOMEPAGE PRESERVATION
Homepage source: le-manager-homepage-v5-clickable.html supplied by the owner.
The source is kept byte-for-byte in templates/approved-v5.html (SHA256:
1abafa5ea1b3d2e305c9bf2dddf376b81a19139d2e2016b3392a0f9bdcbe9ab7).
All original text, images, inline CSS, sections, cards and responsive rules are retained.
The old theme stylesheet is NOT loaded on the homepage. WordPress hooks and a
behavior-only script connect the existing buttons to accounts and content archives.
Homepage content remains the exact approved static content, including its example
names, counts, badges and sponsor placeholders. Publishing records updates the
connected directory/archive/detail pages, not the preserved homepage artwork.
No example profiles or claims are imported as verified database records.
The V5 header and footer are reused on the additional functional pages.
The original modal is retained for roadmap actions with no existing backend workflow.
Original mobile rules are preserved, including the original hidden navigation behavior.
The original HTML references remote Unsplash images; those URLs are unchanged.
Backend based on donteck/le-manager-hub Core 0.1.0 with privacy and validation fixes.

Booking requests are inquiries, not confirmed reservations. Payments, contracts,
automated rewards, event ticketing, messaging and automatic fan-level progression
were roadmap items in the original Core and are not implemented in this release.
Member profiles/listings are published and maintained by site editors; registering a
professional/company account does not automatically publish a directory listing.

