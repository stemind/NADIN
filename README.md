https://yuba.ch/nadin: NADIN is a PHP program made to provide big band musicians with the needed sheet music and recordings thereof by means of personal access data.
To that end, the PDF files can be taken apart to make them available selectively, so that e.g. saxophonists can only obtain the saxophone score etc. This ensures that the arrangements are protected and do not spread uncontrollably. Substitutes can be registered in the system, too; they receive a login carrying an expiration date.
In NADIN, a band’s complete music library can be managed. NADIN administrators can put together the set lists of gigs by mouse clicks and make them available to the band members, including download links for the appropriate notes and recordings. Past gigs can be archived. In the administration, user rights can be defined (e.g. access only to the sax score for the current gigs, but not to archived gigs, and not to the music library).

NADIN is purely file-based; therefore, it needs no database. It was developed by Urs Petermann and Stefan Minder for the Uptown Big Band Bern and is available to all interested parties free of charge. Any warranty and liability is excluded.

Version notes: [V8 — compact original interface](docs/V8.md).

[V9 — forScore setlists and reusable notes](docs/V9-forScore.md), based on V8.

Version notes: [V11 — personal passwords and simple onboarding](docs/V11.md).

[V12 — compact gig administration, clear permissions and safer downloads](docs/V12.md), based on V11.

[V13 — progressive brute-force protection with a clear retry countdown](docs/V13.md), including all V12 improvements.

[V14 — compact, readable ZIP downloads](docs/V14.md), based on V13.

[V15 — a cleaner, consistent interface with compact spacing](docs/V15.md), including V14 downloads.

[V16 — optional SAML/OIDC, verified ZIP downloads and a Main-to-V15 regression review](docs/V16.md). Local onboarding stays unchanged; federation setup is documented [here](docs/V16-federation.md).
