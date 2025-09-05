import dotenv from 'dotenv';
dotenv.config(); // ✅ Make sure .env is loaded

import mysql from 'mysql2/promise';
import { drizzle } from 'drizzle-orm/mysql2';
import * as schema from '@shared/schema';

// Check if DATABASE_URL is available 
let db: any;

if (process.env.DATABASE_URL) {
  const connection = mysql.createPool(process.env.DATABASE_URL);
  db = drizzle(connection, { schema, mode: "default" });
} else {
  console.log('DATABASE_URL not found. Using in-memory storage.');
  // Create a mock db object for in-memory storage
  db = null;
}

export { db };
