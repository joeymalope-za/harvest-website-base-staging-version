import json

import pandas as pd
from openai import OpenAI
from joblib import Memory
import argparse
import os
import tqdm

from forms_to_json import csv_to_obj

client = None
memory = Memory(".cache", verbose=0)

def enrich(input_dir, out_file):
    records = csv_to_obj(input_dir)
    df = pd.DataFrame.from_records(records)
    df = df.loc[:, ['Zoho ID', 'Condition or Symptom Name']]
    df.rename(columns={'Zoho ID': 'Record Link ID'}, inplace=True)
    df['Medical synonyms'] = ''
    df['Layman synonyms'] = ''
    for i, row in tqdm.tqdm(df.iterrows()):
        if row['Condition or Symptom Name'] != '':
            medical_condition = row['Condition or Symptom Name']
            prompt = f"Provide synonyms for the medical condition I'll give you. I need two types of synonyms. The first is the proper medical names for the condition, which doctor may use, let's call it 'medical_synonyms'. The second is the synonyms which the non-doctor may use, let's call it 'layman_synonyms'. Respond with JSON with these two fields. Don't generate too vague synonyms, like 'abdominal pain'. Don't include related or similar conditions. Don't invent any layman terms, which don't exist. The condition I need synonyms for is '{medical_condition}'"
            synonyms = generate_synonyms(prompt, -1)
            df.at[i, 'Medical synonyms'] = ', '.join(synonyms['medical_synonyms'])
            df.at[i, 'Layman synonyms'] = ', '.join(synonyms['layman_synonyms'])
    df.to_csv(out_file, sep=';', index=False)


@memory.cache
def generate_synonyms(prompt, attempt):
    response = client.chat.completions.create(
        model="gpt-4",
        messages=[
            {
                "role": "user",
                "content": prompt,
            }
        ],
        temperature = 0.2,
        top_p = 0.1
    )
    return json.loads(response.choices[0].message.content)

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Create condition form enrichment CSV using AI")
    parser.add_argument("--input_dir", help="The directory containing the Zoho forms exported CSV files")
    parser.add_argument("--openai_key", help="OpenAI API key")
    args = parser.parse_args()
    client = OpenAI(
        api_key=args.openai_key,
    )
    out_file = os.path.join(args.input_dir, 'enrichment.csv')
    enrich(args.input_dir, out_file)