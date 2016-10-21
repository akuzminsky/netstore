/*$Id: dbf.h,v 1.1.1.1 2007/11/29 15:29:46 ingoth Exp $*/
#ifndef _DBF_TABLES_
#define _DBF_TABLES_

/*
 * table file header
 * Standard dBASE Header
 * Additional dBASE Header FLAGS
 * Since version IV 2.0 dBASE has introduced new flags, like:
 * - incomplete transmission
 * - encryption
 * - mdx
 * - language
 * Offsets of this header are the same in all versions of dBASE
 */
struct DB_HEADER {
	unsigned char version; 			/* Byte: 0; dBase version */
	unsigned char last_update[3]; 	/* Byte: 1-3; date of last update */
    //unsigned long records; 		/* Byte: 4-7; number of records in table */
    unsigned int records;			/* Byte: 4-7; number of records in table */
	u_int16_t header_length;		/* Byte: 8-9; number of bytes in the header */
	u_int16_t record_length;		/* Byte: 10-11; number of bytes in the record */
	unsigned char reserved01[2];	/* Byte: 12-13; reserved, see specification of dBase databases */
	unsigned char transaction;		/* Byte: 14; Flag indicating incomplete transaction */
	unsigned char encryption;		/* Byte: 15; Encryption Flag */
	unsigned char reserved02[12];	/* Byte: 16-27; reserved for dBASE in a multiuser environment*/
	unsigned char mdx;				/* Byte: 28; Production MDX file flag */
	unsigned char language;			/* Byte: 29; Language driver ID */
	unsigned char reserved03[2];	/* Byte: 30-31; reserved, filled with zero */
};

/* The field descriptor array */
/* Offsets of this header are the same in all versions of dBASE */
struct DB_FIELD {
	unsigned char field_name[11];	/* Byte: 0-10; fieldname in ASCII */
	unsigned char field_type;		/* Byte: 11; field type in ASCII (C, D, L, M or N) */
	//unsigned long field_adress;		/* Byte: 12-15; field data adress */
	u_int32_t field_adress;		/* Byte: 12-15; field data adress */
	unsigned char field_length;		/* Byte: 16; field length in binary */
	unsigned char field_decimals;	/* Byte: 17; field decimal count in binary */
	unsigned char reserved[13];		/* Byte: 18-30; reserved */
	unsigned char mdx;				/* Byte: 31; Production MDX field flag */
};

#endif
