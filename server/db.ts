import dotenv from 'dotenv';
dotenv.config(); // ✅ Make sure .env is loaded

import mysql from 'mysql2/promise';
import { drizzle } from 'drizzle-orm/mysql2';
import * as schema from '@shared/schema';

// Check if DATABASE_URL is available and valid for MySQL
let db: any;

if (process.env.DATABASE_URL && process.env.DATABASE_URL.includes('mysql')) {
  try {
    const connection = mysql.createPool(process.env.DATABASE_URL);
    db = drizzle(connection, { schema, mode: "default" });
    console.log('Connected to MySQL database');
  } catch (error) {
    console.log('MySQL connection failed, using in-memory storage:', error);
    db = null;
  }
} else {
  console.log('No MySQL DATABASE_URL found. Using in-memory storage.');
  // Create a mock db object for in-memory storage
  db = null;
}

export { db };
