DROP TABLE trains;
CREATE TABLE trains
(
  id bigserial NOT NULL,
  formal_id character varying(24) NOT NULL,
  actual_to date NOT NULL,
  periodical character varying(24) NOT NULL,
  CONSTRAINT trains_pkey PRIMARY KEY (id),
  CONSTRAINT trains_formal_id_periodical_key UNIQUE (formal_id, periodical)
)

DROP TABLE stations;
CREATE TABLE stations
(
  id bigserial NOT NULL,
  "name" character varying(255),
  CONSTRAINT stations_pkey PRIMARY KEY (id),
  CONSTRAINT stations_name_key UNIQUE (name)
)

DROP TABLE schedule;
CREATE TABLE schedule
(
  id_train bigint NOT NULL,
  id_station bigint NOT NULL,
  arrival time without time zone,
  dispatch time without time zone,
  CONSTRAINT schedule_id_station_fkey FOREIGN KEY (id_station)
      REFERENCES stations (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT schedule_id_train_fkey FOREIGN KEY (id_train)
      REFERENCES trains (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)

DROP INDEX schedule_unique_with_arrival;
CREATE UNIQUE INDEX schedule_unique_with_arrival
  ON schedule
  USING btree
  (id_train, id_station, arrival)
  WHERE arrival IS NOT NULL;

DROP INDEX schedule_unique_with_dispatch;

CREATE UNIQUE INDEX schedule_unique_with_dispatch
  ON schedule
  USING btree
  (id_train, id_station, dispatch)
  WHERE dispatch IS NOT NULL;

DROP TABLE ways;
CREATE TABLE ways
(
  id_train bigint,
  id_start_station bigint,
  id_end_station bigint,
  CONSTRAINT ways_id_end_station_fkey FOREIGN KEY (id_end_station)
      REFERENCES stations (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT ways_id_start_station_fkey FOREIGN KEY (id_start_station)
      REFERENCES stations (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT ways_id_train_fkey FOREIGN KEY (id_train)
      REFERENCES trains (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT ways_id_train_id_start_station_id_end_station_key UNIQUE (id_train, id_start_station, id_end_station)
)