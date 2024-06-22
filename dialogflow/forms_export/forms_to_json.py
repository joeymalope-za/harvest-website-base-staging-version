"""
This script converts Zoho Form CSV export files into JSON, and, optionally, puts the data into Mongo DB

Make sure to update subform_mapping, if required.

Installation:

pip install -r requirements.txt
"""
import pandas as pd
from pymongo import MongoClient
from pymongo.server_api import ServerApi
from os import path
import sys
import json
import argparse

main_file_name = 'Condition_Records_1.csv'

collection_name = 'conditions'
db_name = 'harvest'

subform_mapping = {
    "Dosage": "Condition_SubForm_Records_1",
    "Benefits": "Condition_SubForm4_Records_1",
    "Other treatment options": "Condition_SubForm3_Records_1",
    "Meds for therapies": "Condition_SubForm2_Records_1",
    "Interactions": "Condition_SubForm1_Records_1",
    "Enrichment": "enrichment"
}

subform_cache = {}

def read_subform(input_dir, subform_file_name, record_link_id):
    if path.exists(path.join(input_dir, f"{subform_file_name}.csv")):
        if (input_dir+subform_file_name in subform_cache):
            df = subform_cache[input_dir+subform_file_name]
        else:
            df = pd.read_csv(path.join(input_dir, f"{subform_file_name}.csv"),
                             sep=',' if 'SubForm' in subform_file_name else ';')
            subform_cache[input_dir+subform_file_name] = df
        subform_data = df[df['Record Link ID'] == record_link_id]
        subform_data = subform_data.drop('Record Link ID', axis=1)
        subform_data.fillna("", inplace=True)
        return subform_data.to_dict(orient='records')
    else:
        return []

def csv_to_obj(input_dir, mongodb_conn_str=None):
    client = None
    if mongodb_conn_str:
        client = MongoClient(mongodb_conn_str, server_api=ServerApi('1'))

    # Load the main CSV into a DataFrame
    df_main = pd.read_csv(path.join(input_dir, main_file_name))
    df_main.fillna("", inplace=True)
    df_main['Zoho ID'] = df_main['Record Link ID']
    df_main.drop(['Record Link ID'], axis=1, inplace=True)
    df_main = df_main[df_main['Condition or Symptom Name'] != '']
    records = []
    # Iterate over the rows in the main DataFrame
    if client:
        print('Processing...')
    for _, row in df_main.iterrows():
        record = row.to_dict()
        for field_name, file_name in subform_mapping.items():
            subform_data = read_subform(input_dir, file_name, record['Zoho ID'])
            record[field_name] = subform_data
        record["CanInhale"] = any(d for d in record["Dosage"] if d["Route"] == "Inhaled")
        records.append(record)
    # create full output CSV for debug
    df_debug = pd.DataFrame.from_records(records)
    df_debug.to_csv('conditions_full.csv', index=False)
    if client:
        col = client[db_name][collection_name]
        col.drop()
        col = client[db_name].create_collection(collection_name)
        print('Pushing data to MongoDB...')
        col.insert_many(records)
        print(f'Records created: {len(records)}')
        client.close()
    return records


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Convert Zoho Forms data export to JSON, optionally, putting it to MongoDB")
    parser.add_argument("--input_dir", help="The directory containing the CSV files")
    parser.add_argument("--mongo", help="The MongoDB connection string")
    args = parser.parse_args()
    if args.mongo:
        input('This will delete existing MongoDB collection. Press any key to continue or Ctrl+C to cancel.\n')
    csv_to_obj(args.input_dir, args.mongo)

