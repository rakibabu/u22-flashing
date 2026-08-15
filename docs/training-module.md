# Trainingsmodule

## Doel

Coaches bereiden een training samen uit de bestaande `exercise_library_items`-bibliotheek, voeren hem mobiel uit en bewaren de werkelijke uitvoering met aanwezigheid en evaluatie.

## Modellen en privacy

`TrainingSession` bevat planning en geordende `TrainingBlock`-records. `TrainingRun` en `TrainingBlockRun` houden daadwerkelijke tijden, blokstatus en notities apart van de planning. `TrainingAttendance` koppelt per uitvoering aan de bestaande `players`-tabel. Alleen `coach` mag wijzigen of uitvoeren; `coach_viewer` mag lezen. Spelers hebben geen coachroutes, coachnotities of private oefenmedia.

Oefenmedia staat op de `local` disk en wordt uitsluitend geleverd via de geautoriseerde media-controller. Oefeningen worden gearchiveerd (`archived_at`) in plaats van verwijderd.

## Snapshots en dupliceren

`CreateExerciseSnapshot` kopieert alle uitvoeringsrelevante oefengegevens naar `training_blocks.exercise_snapshot`. Daardoor verandert een historische training niet na bibliotheekwijzigingen. `DuplicateTrainingSession` kopieert sessie en blokken in één transactie, behoudt snapshots, verwijst naar de bron en maakt nooit runs, aanwezigheid of evaluaties mee.

## Timer en uitvoering

De mobiele uitvoeringspagina toont één blok per keer. Alpine berekent elke seconde de weergave uit vaste timestamps; er is geen seconde-polling naar Livewire. De server bewaart start, pauze, totale pauzeduur, blokstart/einde, toegevoegde seconden en werkelijke duur. Een actieve run wordt bij heropenen hervat. Wake Lock is slechts een progressive enhancement.

## PWA en offline

Het bestaande manifest en de clubiconen zijn hergebruikt. De service worker cachet alleen statische assets, nooit geauthenticeerde HTML of privédata. Met **Maak offline beschikbaar** wordt de huidige training expliciet in IndexedDB bewaard. Offline navigatie naar de uitvoeringsroute opent een volledige mobiele trainingsmodus met timer, pauze/hervat, vorige/volgende, overslaan, +2 minuten en lokale notities. Elke wijziging komt in een kleine eventqueue. De route `coach.training-runs.offline-events` verwerkt die queue op UUID en sequence idempotent; bij een herstelde verbinding kan de coach handmatig synchroniseren en de app probeert dit ook automatisch. Lokale trainingsdata wordt bij uitloggen verwijderd.

## Routes

- `/coach/oefeningen`
- `/coach/trainingen`, `/nieuw`, `/{training}/bewerken`, `/{training}/uitvoeren`, `/{training}/evalueren`
- `POST /coach/training-runs/{trainingRun}/offline-events`

## Handmatig testen

1. Meld aan als coach, maak een oefening met coaching points en archiveer hem; controleer dat de kaart verdwijnt.
2. Maak een training, voeg een oefening en een tekstblok toe, verplaats ze en publiceer.
3. Start uitvoering op mobiel, pauzeer/hervat, voeg twee minuten toe, voeg een notitie toe en rond af.
4. Registreer aanwezigheid en evaluatie; dupliceer daarna de training en controleer dat de uitvoering niet is gekopieerd.
5. Meld aan als speler en controleer dat coachroutes en oefenmedia 403 geven.

## Bewuste MVP-grenzen

Er is geen teammodel aanwezig, dus sessies gebruiken geen kunstmatige teamkoppeling. Offline afbeeldingen en PDF's worden nog niet gedownload: de offline runner gebruikt uitsluitend de trainingssnapshot en tekstuele coachinginformatie.
