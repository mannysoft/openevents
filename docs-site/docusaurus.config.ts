import {themes as prismThemes} from 'prism-react-renderer';
import type {Config} from '@docusaurus/types';
import type * as Preset from '@docusaurus/preset-classic';

const repository = 'mannysoft/openevents';
const branch = 'main';

// Overridable so forks and CI can deploy to a different origin or path.
const siteUrl = process.env.DOCS_URL ?? 'https://mannysoft.github.io';
const baseUrl = process.env.DOCS_BASE_URL ?? '/openevents/';
const docsEditUrl = `https://github.com/${repository}/edit/${branch}/docs/`;
const blogEditUrl = `https://github.com/${repository}/edit/${branch}/docs/`;

const config: Config = {
  title: 'Events Documentation',
  tagline: 'Multi-organiser ticketing on Laravel and Livewire',
  favicon: 'img/favicon.svg',

  future: {
    v4: true,
  },

  url: siteUrl,
  baseUrl,

  organizationName: 'mannysoft',
  projectName: 'openevents',

  onBrokenLinks: 'throw',
  onBrokenAnchors: 'throw',

  i18n: {
    defaultLocale: 'en',
    locales: ['en'],
  },

  markdown: {
    format: 'detect',
    mermaid: true,
  },

  presets: [
    [
      'classic',
      {
        docs: {
          path: 'content',
          routeBasePath: 'docs',
          sidebarPath: './sidebars.ts',
          editUrl: docsEditUrl,
          showLastUpdateTime: false,
        },
        blog: {
          blogTitle: 'Events changelog',
          blogDescription: 'Release notes and articles about the Events ticketing platform.',
          showReadingTime: true,
          editUrl: blogEditUrl,
          feedOptions: {
            type: ['rss', 'atom'],
            xslt: true,
          },
          onInlineTags: 'warn',
          onInlineAuthors: 'warn',
          onUntruncatedBlogPosts: 'warn',
        },
        theme: {
          customCss: './src/css/custom.css',
        },
      } satisfies Preset.Options,
    ],
  ],

  themes: [
    '@docusaurus/theme-mermaid',
    [
      '@easyops-cn/docusaurus-search-local',
      {
        hashed: true,
        language: ['en'],
        docsDir: 'content',
        docsRouteBasePath: '/docs',
        blogRouteBasePath: '/blog',
        indexBlog: true,
        highlightSearchTermsOnTargetPage: true,
        searchResultLimits: 8,
        searchBarShortcutHint: true,
      },
    ],
  ],

  themeConfig: {
    image: 'img/social-card.png',
    metadata: [
      {
        name: 'description',
        content:
          'User and developer documentation for Events, a multi-organiser ticketing platform built on Laravel and Livewire with Stripe Connect.',
      },
      {
        name: 'keywords',
        content:
          'ticketing, Laravel, Livewire, Stripe Connect, event management, documentation',
      },
    ],
    colorMode: {
      defaultMode: 'light',
      disableSwitch: false,
      respectPrefersColorScheme: true,
    },
    navbar: {
      title: 'Events',
      logo: {
        alt: 'Events documentation',
        src: 'img/logo.svg',
      },
      items: [
        {
          type: 'docSidebar',
          sidebarId: 'docsSidebar',
          position: 'left',
          label: 'Documentation',
        },
        {to: '/docs/user', label: 'User guide', position: 'left'},
        {to: '/docs/developer', label: 'Developer guide', position: 'left'},
        {to: '/blog', label: 'Changelog', position: 'left'},
        {
          href: `https://github.com/${repository}`,
          label: 'GitHub',
          position: 'right',
        },
      ],
    },
    docs: {
      sidebar: {
        hideable: true,
        autoCollapseCategories: true,
      },
    },
    footer: {
      style: 'dark',
      links: [
        {
          title: 'User guide',
          items: [
            {label: 'Getting started', to: '/docs/user/getting-started'},
            {label: 'Stripe Connect', to: '/docs/user/stripe-connect'},
            {label: 'Attendees and check-in', to: '/docs/user/check-in'},
            {label: 'Payouts and earnings', to: '/docs/user/payouts-and-earnings'},
          ],
        },
        {
          title: 'Developer guide',
          items: [
            {label: 'Local setup', to: '/docs/developer/local-setup'},
            {label: 'Architecture', to: '/docs/developer/architecture'},
            {label: 'Payments and webhooks', to: '/docs/developer/payments-and-webhooks'},
            {label: 'Deployment and operations', to: '/docs/developer/deployment-and-operations'},
          ],
        },
        {
          title: 'Resources',
          items: [
            {
              label: 'Source code',
              href: `https://github.com/${repository}`,
            },
            {
              label: 'Report an issue',
              href: `https://github.com/${repository}/issues`,
            },
            {
              label: 'Stripe Connect docs',
              href: 'https://docs.stripe.com/connect',
            },
          ],
        },
      ],
      copyright: `Copyright © ${new Date().getFullYear()} Events. Built with Docusaurus.`,
    },
    prism: {
      theme: prismThemes.github,
      darkTheme: prismThemes.dracula,
      additionalLanguages: ['php', 'bash', 'json', 'yaml', 'ini', 'diff', 'sql', 'http'],
    },
  } satisfies Preset.ThemeConfig,
};

export default config;
