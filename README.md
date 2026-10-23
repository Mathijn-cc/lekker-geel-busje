# Lekker Geel thema

Custom WordPress-thema voor lekkergeelbusje.nl, gebouwd door Creators Connect.

## Werkwijze

- Deze repo is de enige bron van het thema. Pas nooit bestanden direct op de server aan.
- Elke push naar `main` wordt automatisch uitgerold naar de staging (lgb.bijnadaar.nl), zie het tabblad Actions.
- Bij elke uitrol wordt eerst een back-up van het thema op de server gemaakt (`~/thema-backups/`, de laatste tien blijven bewaard).
- Het versienummer in de browser wordt per uitrol automatisch opgehoogd, dus hard verversen is niet nodig.

## Benodigd

- Repo secret `STAGING_SSH_KEY`: de private SSH-sleutel voor de SiteGround-gebruiker van de staging.
