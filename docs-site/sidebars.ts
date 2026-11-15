import type {SidebarsConfig} from '@docusaurus/plugin-content-docs';

const sidebars: SidebarsConfig = {
  docsSidebar: [
    'index',
    {
      type: 'category',
      label: 'User guide',
      collapsible: true,
      collapsed: false,
      link: {type: 'doc', id: 'user/index'},
      items: [
        'user/getting-started',
        'user/account-and-sign-in',
        'user/organisers',
        'user/stripe-connect',
        'user/creating-events',
        'user/tickets',
        'user/discount-codes',
        'user/questions',
        'user/orders-and-refunds',
        'user/attendees',
        'user/check-in',
        'user/messaging',
        'user/payouts-and-earnings',
        'user/for-attendees',
        'user/troubleshooting',
      ],
    },
    {
      type: 'category',
      label: 'Developer guide',
      collapsible: true,
      collapsed: false,
      link: {type: 'doc', id: 'developer/index'},
      items: [
        'developer/local-setup',
        'developer/configuration',
        'developer/architecture',
        'developer/domain-model',
        'developer/money-and-currency',
        'developer/order-lifecycle',
        'developer/stripe-connect',
        'developer/payments-and-webhooks',
        'developer/livewire-components',
        'developer/routes-reference',
        'developer/authorization',
        'developer/testing',
        'developer/code-style',
        'developer/mail-and-notifications',
        'developer/deployment-and-operations',
        'developer/extending',
      ],
    },
  ],
};

export default sidebars;
