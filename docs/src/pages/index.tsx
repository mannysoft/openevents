import type {ReactNode} from 'react';
import clsx from 'clsx';
import Link from '@docusaurus/Link';
import Layout from '@theme/Layout';
import Heading from '@theme/Heading';

import styles from './index.module.css';

type Card = {
 title: string;
  body: string;
  to: string;
};

const startHere: Card[] = [
  {
    title: 'Getting started',
    body: 'Sign up, create an organiser, connect Stripe and publish your first event.',
    to: '/docs/user/getting-started',
  },
  {
    title: 'User guide',
    body: 'Run events day to day: tickets, discounts, orders, refunds, check-in and messaging.',
    to: '/docs/user',
  },
  {
    title: 'Developer guide',
    body: 'Set up the app locally, understand the domain model and ship it to production.',
    to: '/docs/developer',
  },
  {
    title: 'Payments and webhooks',
    body: 'How destination charges, the application fee and Connect onboarding fit together.',
    to: '/docs/developer/payments-and-webhooks',
  },
];

function CardLink({title, body, to}: Card): ReactNode {
  return (
    <Link className={styles.card} to={to}>
      <span className={styles.cardTitle}>{title}</span>
      <span className={styles.cardBody}>{body}</span>
    </Link>
  );
}

export default function Home(): ReactNode {
  return (
    <Layout
      title="Documentation"
      description="User and developer documentation for Events, a multi-organiser ticketing platform built on Laravel and Livewire with Stripe Connect.">
      <header className={styles.hero}>
        <div className={styles.heroInner}>
          <span className={styles.eyebrow}>Laravel · Livewire · Stripe Connect</span>
          <Heading as="h1" className={styles.title}>
            Everything you need to run events
          </Heading>
          <p className={styles.subtitle}>
            Events is a small, deliberately bare ticketing platform. Organisers sell
            tickets through their own Stripe account and keep the difference after the
            platform fee. These docs cover both sides: the people running events and the
            people maintaining the code.
          </p>
          <div className={styles.actions}>
            <Link className="button button--primary button--lg" to="/docs/user/getting-started">
              I'm an organiser
            </Link>
            <Link className="button button--secondary button--lg" to="/docs/developer/local-setup">
              I'm a developer
            </Link>
          </div>
        </div>
      </header>

      <main>
        <section className={styles.section}>
          <div className={styles.sectionInner}>
            <Heading as="h2" className={styles.sectionTitle}>
              Start here
            </Heading>
            <p className={styles.sectionLead}>
              Pick the path that matches what you are trying to do.
            </p>
            <div className={styles.cards}>
              {startHere.map((card) => (
                <CardLink key={card.to} {...card} />
              ))}
            </div>
          </div>
        </section>

        <section className={clsx(styles.section)}>
          <div className={styles.sectionInner}>
            <Heading as="h2" className={styles.sectionTitle}>
              What the platform does
            </Heading>
            <p className={styles.sectionLead}>
              One codebase covers public ticketing, organiser administration and payments.
            </p>
            <div className={styles.cards}>
              <div className={styles.card}>
                <span className={styles.cardTitle}>Sell tickets</span>
                <span className={styles.cardBody}>
                  Public event pages, tiered tickets, sales windows, discount codes and
                  attendee questions, with checkout backed by the Stripe Payment Element.
                </span>
              </div>
              <div className={styles.card}>
                <span className={styles.cardTitle}>Get paid directly</span>
                <span className={styles.cardBody}>
                  Destination charges transfer each sale to the organiser's own Express
                  account while the platform retains its application fee.
                </span>
              </div>
              <div className={styles.card}>
                <span className={styles.cardTitle}>Run the door</span>
                <span className={styles.cardBody}>
                  QR check-in, attendee search, CSV and XLSX exports, refunds from the
                  dashboard and an earnings report per event.
                </span>
              </div>
            </div>
          </div>
        </section>
      </main>
    </Layout>
  );
}
