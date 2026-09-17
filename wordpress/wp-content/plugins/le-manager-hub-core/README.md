# Le Manager Hub Backend

This folder contains the WordPress backend foundation for **Le Manager Hub — The Global Music Industry Hub**.

## Install

1. Copy `wordpress/wp-content/plugins/le-manager-hub-core/` into the same path on the WordPress server.
2. In WordPress Admin → Plugins, activate **Le Manager Hub Core**.
3. Visit Settings → Permalinks and save once if routes do not appear immediately.
4. Keep WordPress core, PHP and plugins updated and use HTTPS in production.

## Included in v0.1

- Roles: LM Fan, LM Professional, LM Company
- Content: Artists, Professionals, Companies, Events, Opportunities, Bookings, TV, Radio, Podcast
- Taxonomies: Profession, Genre, Country, Event Type, Opportunity Type
- REST registration
- Current-user endpoint
- Searchable directory endpoint
- Booking request creation and member booking history
- Fan follow/unfollow for artists
- REST nonce exposed to the frontend for authenticated requests
- WordPress-native permissions, sanitization and private booking records

## API

Base: `/wp-json/lmh/v1/`

- `POST register`
- `GET me`
- `GET directory?type=lmh_artist&search=`
- `POST bookings`
- `GET bookings/mine`
- `POST follow/{artist_id}`

Authenticated frontend calls should send the WordPress REST nonce in the `X-WP-Nonce` header.

## Next backend phases

v0.2 should add profile ownership/editing, verification workflows, event submissions, opportunity applications, notifications and moderation.

v0.3 should add messaging, availability calendars, booking quotes, contracts and payment-provider integration.

v0.4 should add memberships/subscriptions, ticket/event integrations, fan rewards/Event Squad workflows, analytics and media scheduling.

## Important

The current HTML prototype is the approved visual baseline. The backend is intentionally separated so the UI can be converted into a WordPress theme without losing the approved design.
