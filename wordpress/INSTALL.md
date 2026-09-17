# Le Manager Hub WordPress Installation

The repository now contains both sides of the WordPress application:

- Theme: `wordpress/wp-content/themes/le-manager-hub/`
- Backend plugin: `wordpress/wp-content/plugins/le-manager-hub-core/`

## Deploy

Copy both folders into the matching `wp-content` directories on the production WordPress installation.

In WordPress Admin:
1. Plugins → activate **Le Manager Hub Core**.
2. Appearance → Themes → activate **Le Manager Hub**.
3. Settings → Permalinks → Save Changes.
4. Add Artists, Professionals, Companies, Events and Opportunities in the backend.

## Admin

Open `/wp-admin/` on the installed domain.

## Backend API

`/wp-json/lmh/v1/`

The theme and backend are separated intentionally: design belongs to the theme; platform data and business logic belong to the Core plugin.
