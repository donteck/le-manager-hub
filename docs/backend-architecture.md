# Backend Architecture

## Platform layers

**Presentation:** approved Le Manager Hub HTML → WordPress theme.

**Application:** Le Manager Hub Core plugin. Business rules and REST API live here instead of in the theme.

**Identity:** WordPress users with LM Fan, LM Professional and LM Company roles. Administrators retain WordPress-native administration.

**Content:** custom post types for artists, professionals, companies, events, opportunities and the TV/Radio/Podcast media network.

**Transactions:** private booking records owned by the requesting user. Later phases add quotes, contracts, payments and reviews.

**Community:** fans follow artists now; later phases add personalized feeds, Fan/Insider/Ambassador/VIP progression and Event Squad participation.

## Data relationships

User → Professional/Artist/Company profile  
Fan User → follows → Artist  
Professional/Company → publishes → Event / Opportunity  
User → creates → Booking → targets Artist/Professional  
Artist/Professional → appears in → Events / Media / Opportunities  
TV/Radio/Podcast → links back to → Artists / Professionals / Companies

## Security baseline

Use WordPress authentication and capabilities; sanitize all incoming values; use REST nonces for authenticated browser requests; keep booking records private; do not store payment card data in WordPress; use a PCI-compliant payment provider when payments are introduced; add rate limiting/WAF controls at hosting/CDN level before public launch.
