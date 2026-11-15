---
slug: introducing-events-documentation
title: Introducing the Events documentation
authors: [docs-team]
tags: [documentation]
---

The platform's documentation now lives in one place, built with Docusaurus and
published alongside the code it describes.

<!-- truncate -->

Until now the only written reference for Events was the `README.md` at the root of the
repository. That covered setup and the Stripe Connect model well enough for developers,
but it said nothing about running an event: publishing, discounts, refunds, check-in or
payouts.

The new site splits the material in two:

- A **user guide** for organisers, covering the full path from signing up and completing
  Stripe onboarding through to check-in and the earnings report.
- A **developer guide** covering local setup, configuration, architecture, the domain
  model, the order lifecycle, Stripe and webhook handling, testing and deployment.

Everything is versioned with the application, so a change to behaviour can ship with the
matching documentation change in the same pull request. Spotted something out of date?
Every page has an "Edit this page" link in the footer.
