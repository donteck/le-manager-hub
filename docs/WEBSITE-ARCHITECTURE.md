# LE MANAGER HUB — Website Architecture

## Brand
**The Global Music Industry Hub**  
**Talent • Brand • Opportunities • Community**

Le Manager Hub serves two connected audiences: the music industry and music fans. TV, Radio, Podcast, Events, Opportunities, Booking, and the global directory connect both sides.

## Main Navigation
- Home
- Discover
- Professionals
- Opportunities
- Events
- Media
- Fans
- Bookings
- Join Le Manager Hub
- Login / Dashboard

## 1. Home
Hero → Global Search → Statistics → Featured Artists → Featured Professionals → Directory → Opportunities → TV/Radio/Podcast → News → Events → Music Discovery → Global Network → Verification → Fan Community → Membership → Partners → Join CTA → Footer.

Primary actions: **JOIN LE MANAGER HUB** and **EXPLORE THE HUB**. Registration later separates into **JOIN AS A PROFESSIONAL** and **JOIN AS A FAN**.

## 2. Discover
- Artists
- Industry Professionals
- Companies & Organizations
- Music
- Videos
- Global Directory

## 3. Global Directory
Route: `/directory/`

Search/filter by name, profession, city, country, genre, language, service, market, availability, and verification status.

Professional categories include Artists, Managers, Producers, Songwriters, Engineers & Studios, Promoters, Booking Agents, Labels & Distributors, PR/Publicists, Entertainment Lawyers, DJs/Radio, Videographers, Photographers, Graphic Designers, Influencers/Creators, Actors, Models, Dancers, Venues, Sponsors & Brands.

## 4. Professional & Artist Profiles
Professional profiles include photo, cover, badge, role, location, bio, company, services, genres, languages, years active, clients, portfolio, credits, media, events, availability, social links, contact and booking.

Artist profiles additionally include music catalog, streaming links, EPK, discography, videos, management, booking agent, press and tour dates.

## 5. Companies & Organizations
Dedicated profiles for labels, management companies, booking agencies, PR firms, studios, radio stations, TV networks, venues, festivals, distributors, brands and sponsors.

## 6. Opportunities
Route: `/opportunities/`

Artist bookings, jobs, casting calls, collaborations, festivals, radio submissions, music submissions, sponsorships, production opportunities, label opportunities and touring.

Each listing should include poster/company, location, deadline, requirements, compensation when provided, and an Apply action.

## 7. Events
Route: `/events/`

Discover Events, Concerts, Festivals, Showcases, Conferences, Industry Events, Le Manager Events, My Events and Submit an Event.

Event pages include poster, date/time, venue, map, artists, promoter, description, sponsors, ticket information, follow/share actions and calendar integration.

## 8. Media Network
### Le Manager TV — `/tv/`
Live TV, shows, interviews, live sessions, music videos, premieres, documentaries, backstage, event coverage and industry programs.

### Le Manager Radio — `/radio/`
Listen Live, Now Playing, schedule, DJs, shows, artist spotlights, premieres, interviews, charts and music submissions.

### Le Manager Podcast — `/podcast/`
Episodes and conversations covering artists, business, management, production, touring, legal, marketing and career development.

### News & Music
Industry news, interviews, premieres, charts, releases, playlists, genres and global discovery.

## 9. Le Manager Fans
Route: `/fans/`

Fan accounts can follow artists, discover events, receive alerts, save events, consume TV/Radio/Podcast content, participate in giveaways and experiences, and join community activities.

Fan progression: **LM Fan → LM Insider → LM Ambassador → LM VIP**.

## 10. Le Manager Event Squad
Route: `/fans/event-squad/`

An organized real-world community that can participate in concerts, festivals, showcases, premieres, conferences and selected partner events while supporting artists and representing the Le Manager movement.

## 11. Bookings
Route: `/bookings/`

Long-term workflow: **Discover → View Profile → Request Booking → Availability → Quote → Negotiate → Agreement → Contract → Payment → Event → Review**.

## 12. Verification
Route: `/verification/`

Levels: **Listed → Verified ✓ → Le Manager Pro ✓ → Le Manager Elite ✓**.

Each level should have documented criteria and benefits.

## 13. Membership
Professional/business plans: **Free | Pro | Premium | Business**.

Fans use a separate fan-oriented membership and experience model.

## 14. Registration
`JOIN LE MANAGER HUB` first asks: **I'M A PROFESSIONAL** or **I'M A FAN**.

Professional registration then branches by profession and uses role-specific fields. Fan registration is intentionally simpler and captures interests such as artists, genres, cities and events.

## 15. Professional Dashboard
Overview, Profile, Views, Messages, Connections, Bookings, Opportunities, Applications, Events, Music & Media, EPK/Portfolio, Documents, Saved Profiles, Verification, Promotions, Membership, Analytics and Settings.

## 16. Fan Dashboard
My Feed, Following, Artists, Events, Saved Events, Event Links, TV, Radio, Podcast, Fan Experiences, Event Squad, Rewards/Status, Notifications and Profile.

## 17. Administration
Users, Professionals, Artists, Fans, Companies, Verification, Music, Videos, TV, Radio, Podcasts, News, Events, Opportunities, Bookings, Applications, Memberships, Payments, Sponsors, Advertising, Reports, Moderation, Analytics, Notifications and Site Settings.

## Platform Engines
1. **Discovery** — artists, professionals, companies and music.
2. **Business** — opportunities, networking, bookings and professional services.
3. **Media** — TV, Radio, Podcast, News and Music.
4. **Experiences** — events, concerts, festivals and Event Squad.
5. **Community** — fans, artists, professionals, ambassadors and partners.
