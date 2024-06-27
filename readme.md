# Disembark Interface

Interface plugin powering [Disembark](https://disembark.host). Install as a WordPress plugin locally or anywhere you'd like. Disembark Connector links can be directed to that WordPress site:

```
/?disembark_site_url=<url>&disembark_token=<token>
```

### Changelog

**v1.1.0** - June 27th 2024
- Upgraded Vue and Vuetify to v3
- Many new features:
    -  Advanced options to toggle whether to backup database or files
    - Display progress as database tables and files are completed
    - Button to copy download command after backup completed
    - Download link for Disembark Connector
    - Split large database tables in smaller files
    - Ability to start backups from a direct links
- Other improvements like: connection timeouts, better command output and smarter file ordering

**v1.0.0** - June 5th 2024

- Intial release of Disembark Interface which powers [Disembark.host](Disembark.host)
