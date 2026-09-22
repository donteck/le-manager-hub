# LE MANAGER — WORLD MUSIC INDUSTRY
## Master Platform Architecture — V1

This document is the master technical and product blueprint for Le Manager.

## 1. Public Platform
Home, Join Le Manager, Global Directory, Artists, Managers, Producers, Songwriters, Promoters, Booking Agents, PR, DJs/Radio, Engineers/Studios, Videographers, Photographers, Graphic Designers, Dancers, Actors, Models, Influencers, Lawyers, Labels/Distributors, Venues, Sponsors/Brands, Events, Opportunities, FanBase, Le Manager TV, Radio and Podcast.

## 2. Universal Account
Visitor → Join Le Manager → Account → Fan / Artist / Manager / Professional / Promoter / Company / Label.

## 3. LMID
Every person who successfully joins Le Manager receives one permanent unique 9-digit Le Manager Member ID (LMID). LMID is an identifier, never a password, PIN, SSN, or authentication secret.

LMID links the member to account type, level, status, join date, verification, digital/physical card status, Ambassador status, benefits eligibility and audit data.

## 4. Membership Levels
Fan → Insider → Supporter → Ambassador → VIP.
Progression can use transparent criteria including engagement, event participation, verified referrals, FanBase activity, membership history and campaign participation.

## 5. Digital Membership Card
Every member receives a digital membership card containing member name, LMID, level, status, member-since date and secure verification functionality. It must state that it is not a payment card.

## 6. Physical Ambassador Card
Qualified Ambassadors can receive a physical CR80 membership card with member name, LMID, Ambassador level, member-since date, secure verification QR, recruitment QR and card status.

## 7. Secure Membership Verification
Partner scans secure card QR → Le Manager server verifies account/card status, level and benefit eligibility → approved/not approved. The 9-digit LMID alone is never sufficient to authorize benefits.

## 8. Smart QR Engine
QR types: Artist, Ambassador, Event, Venue, Flyer, Poster, Merchandise, Company, Label, Partner and Marketing Campaign.
Each campaign tracks QR ID, campaign ID, owner, destination, artist, referrer, creation date, status, scans and conversions.

## 9. Fan Acquisition QR
Artist → Campaign → QR → Poster/Social/Event/Merch → Person scans → Mobile Le Manager landing page → Artist information → Join the FanBase.

## 10. Consent-Based Fan Registration
The QR scan itself does not secretly obtain personal contact information. During signup, members knowingly provide required account data such as name/email/password and optional or purpose-based fields such as phone, city/country and social profiles.

## 11. Marketing Preferences
Le Manager should separately maintain communication preferences for email, SMS/text, artist/FanBase updates, events and promotional/partner offers, together with appropriate consent/opt-out records.

## 12. Automatic FanBase Connection
QR campaign → Person joins → LMID → Fan account → Campaign identified → Artist identified → Artist followed → Fan added to FanBase → Referrer credited.

## 13. Fan CRM
CRM can associate LMID with permitted member/contact data, communication preferences, social profiles, geography, artists followed, events, campaign source, referring Ambassador, join date, level, engagement and benefits. Private contact data remains permission-controlled.

## 14. FanBase Engine
Artist FanBase: followers, new fans, growth, levels, Ambassadors, superfans, Event Squad, engagement, QR campaigns, referrals, campaign sources, geography, attendance and analytics.

## 15. Ambassador Engine
Fan → Engages → Qualifies → Ambassador → Digital/Physical Card → Personal QR → Referral Link → Recruits Fans.
Dashboard tracks referrals, conversions, benefits, campaigns and level progress.

## 16. Ambassador Recruitment Loop
Ambassador → Personal QR → Scan → Le Manager → Join → New LMID → Fan → Attribution → Engagement → Potential Ambassador → Personal QR → More members → repeat.

## 17. QR Analytics
QR created → scans → unique visitors → landing page → join started → registration completed → LMID → fan → artist follow → engagement → Ambassador conversion.
Metrics include scans, unique scans, conversions, conversion rate, new LMIDs/fans, follows, referrer, campaign, event, venue, time and appropriate aggregate location metrics.

## 18. Professional Profile Engine
Cover, photo, name, role, verification, biography, location, services, languages, availability, achievements, portfolio, website, booking contact, social/music links, Follow and Book Now.

## 19. Verification Engine
Profile created → Pending → Admin Review → Approve/Decline → Published → Verified → Featured. Future layers can include identity, business, artist, credential and fraud/risk review.

## 20. Global Industry Directory
Artists, Managers, Producers, Songwriters, Engineers, Studios, Promoters, Booking Agents, PR, DJs, Radio, Influencers, Videographers, Photographers, Designers, Dancers, Actors, Models, Lawyers, Labels, Distributors, Venues and Sponsors.
Filters: name, profession, genre, country, city, service, availability and verification.

## 21. Booking Marketplace
Client → Profile → Book Now → Date/Budget/Event info → New → Reviewing → Accepted → Confirmed → Completed, with Declined/Cancelled alternatives.
Future: conversation → quote → contract → deposit → confirmation → event → final payment → review.

## 22. Availability & Calendar
Available, Tentative, Booked, Unavailable, Events, Tours and Booking Requests integrated with profiles and booking.

## 23. Messaging
Artist↔Manager, Artist↔Professional, Client↔Talent, Professional↔Professional, booking conversations and administrative messages, with threads, attachments, read state, notifications, blocking, reporting and moderation.

## 24. Events
Organizer, artists, venue, date, location, tickets, QR campaign, RSVP, Event Squad, Ambassadors and FanBase.
Event QR → attendee scan → Le Manager → Join → LMID → Follow performing artist → FanBase growth.

## 25. Event Attendance & Engagement
Registered Fan → Event → Check-in → Attendance → Engagement record → Membership progress.

## 26. Opportunities Marketplace
Jobs, auditions, gigs, casting, music submissions, collaborations, brand deals, sponsorships, touring, internships and industry calls.

## 27. Le Manager Media Network
TV: interviews, performances, programs.
Radio: live stream, DJ shows, artist rotation.
Podcast: interviews, business, education and industry discussions.

## 28. Partner Network
Venues, festivals, studios, hotels, restaurants, transportation, music stores, equipment companies, fashion, labels, media, brands and education partners.

## 29. Discount & Benefits Engine
Member → Partner → Scan card → Secure verification → status/level/benefit/card checks → eligible benefit. Rules can differ by Fan, Insider, Supporter, Ambassador and VIP.

## 30. Rewards Engine
Eligible activity can include verified referrals, event participation, campaign engagement, community contribution and FanBase activity. Outputs can include badges, levels, benefits, access, recognition and partner offers.

## 31. Reviews & Reputation
Completed transaction → Review → Reputation. Reputation can incorporate verification, completed bookings, client reviews, achievements and account standing.

## 32. Notification Engine
Website, email, SMS, push and future app notifications for new fans/follows, bookings, messages, events, verification, Ambassador promotion, benefits and opportunities, respecting applicable communication preferences.

## 33. Marketing Engine
Fan CRM, audience segments, email/SMS campaigns, artist campaigns, event campaigns, Ambassador campaigns, partner campaigns and re-engagement. Segmentation can use artist followed, level, geography, participation, campaign source, engagement and join date.

## 34. Commerce & Payments
Future: booking deposits/payments, platform fees, memberships, premium features, featured listings, advertising, sponsorship, event revenue, invoices, refunds and professional payouts. Payment credentials should be handled by an appropriate payment provider, not directly stored by WordPress.

## 35. Analytics Platform
Artist: fan count/growth, QR scans, conversions, campaigns, event engagement, booking inquiries, profile views and social clicks.
Ambassador: QR scans, referrals, completed joins, recruited fans, conversions, campaigns and level progress.
Platform: members, LMIDs, artists, professionals, companies, active users, fan growth, Ambassadors, QR scans, conversions, bookings, events, opportunities, campaigns, revenue and markets.

## 36. Admin Control Center
Members/LMID, Cards, Profiles, FanBase, Ambassadors, QR Codes, Campaigns, CRM, Communication Preferences, Bookings, Events, Opportunities, Media, Partners, Benefits, Reviews, Payments, Reports, Analytics, Moderation and Security.

## 37. Privacy & Security Layer
Authentication, authorization, roles/permissions, LMID, QR security tokens, card verification, nonces/CSRF protection, rate limiting, audit logs, account status, fraud monitoring, referral-abuse protection, data-access controls and administrative security.
Privacy includes data minimization, communication preferences, consent records where applicable, unsubscribe/opt-out, retention rules and member controls.

## 38. Data Architecture
User → LMID → Membership → Profiles / FanBase → Bookings / Follows → Calendar / Events → Messages / QR → Reviews / Referrals → Payments / Engagement → Analytics / CRM → Le Manager Data Core.

## 39. Technical Stack
Development → GitHub → main branch → GitHub Actions → secure SSH → Uwebbz/Hestia → WordPress → Le Manager theme/API → database → lemanager.app.
Deployment: code → GitHub → Action → PHP validation → production backup → deploy → WordPress validation → success or rollback.

## 40. Current Build Status
Core foundations already exist for the public platform, professional profiles, verification, Admin Control Center, LMID, digital card, booking, FanBase, events, opportunities and media.
Secure QR foundation has begun. Smart QR, QR analytics, Fan CRM, Ambassador system, physical cards, calendar, messaging, notifications, reviews, partner benefits, payments, advanced analytics and mobile app are subsequent phases.

## 41. Master Le Manager Growth Loop

ARTIST
↓
QR CAMPAIGN
↓
PERSON SCANS
↓
LE MANAGER
↓
CONSENT / JOIN
↓
ACCOUNT CREATED
↓
9-DIGIT LMID
↓
DIGITAL MEMBER CARD
↓
BECOMES FAN
↓
FOLLOWS ARTIST
↓
FANBASE CRM
↓
ATTENDS EVENTS
↓
ENGAGES
↓
MEMBERSHIP PROGRESS
↓
AMBASSADOR
↓
DIGITAL + PHYSICAL CARD
↓
PERSONAL QR
↓
BRINGS NEW FANS
↓
EACH NEW FAN RECEIVES LMID
↓
FANBASE GROWS
↓
ARTIST GETS ANALYTICS
↓
AMBASSADOR GETS ATTRIBUTION
↓
LE MANAGER NETWORK GROWS
↓
↺ LOOP REPEATS

## Build Sequence
Smart QR → Scan Tracking → Registration Attribution → Automatic Artist Follow → Fan CRM → QR Analytics → Ambassador Engine → Physical Cards → Events/Check-in → Calendar/Bookings → Messaging → Notifications → Partner Benefits → Reviews → Payments → Advanced Analytics → Mobile App.

---
Le Manager — World Music Industry
Master Architecture V1
