# Events documentation

The user and developer documentation for the Events ticketing platform, built with
[Docusaurus 3](https://docusaurus.io) using the classic TypeScript template.

The site is a standalone npm project so it never interferes with the Laravel app's Vite
build at the repository root.

## Requirements

- Node.js 20 or newer (CI builds on Node 22)
- npm 10 or newer

## Local development

```bash
cd docs-site
npm install
npm run start        # http://localhost:3000/openevents/
```

From the repository root you can also run the workspace scripts:

```bash
npm run docs:dev
npm run docs:build
npm run docs:serve
```

## Checks before opening a pull request

```bash
npm run typecheck    # TypeScript config and page components
npm run build        # production build; broken links and anchors fail the build
```

## Where content lives

```
content/
├── index.md         # /docs/ landing page
├── user/            # organiser and attendee guide  → /docs/user/*
└── developer/       # engineering guide            → /docs/developer/*
blog/                # changelog posts             → /blog/*
```

Sidebar order is declared explicitly in `sidebars.ts`; add a new page to both the folder
and that file.

## Deployment

`.github/workflows/docs.yml` ("Docs site") builds the site on every pull request and
deploys it to GitHub Pages on pushes to `main` and on manual dispatch. The build job runs
`npm ci` and `npm run build` in `docs-site/` on Node 22, then uploads `docs-site/build`
as the Pages artifact; a separate deploy job publishes it.

One-time setup in the repository settings:

1. **Settings → Pages → Build and deployment → Source**: choose **GitHub Actions**.
2. Push to `main`, or run the **Docs site** workflow manually.

The public URL is <https://mannysoft.github.io/openevents/>.

`url` and `baseUrl` default to that location and can be overridden with the `DOCS_URL`
and `DOCS_BASE_URL` environment variables. The workflow sets both from the GitHub context
(`https://<owner>.github.io` and `/<repo>/`), so forks publish to their own path without
editing the config.

## Styling

- `src/css/custom.css` holds the Infima variable overrides (slate palette matching the
  application UI).
- `src/pages/index.tsx` is the landing page; its styles are in `index.module.css`.
