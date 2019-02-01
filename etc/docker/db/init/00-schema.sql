--
-- PostgreSQL database dump
--

-- Dumped from database version 9.6.10
-- Dumped by pg_dump version 9.6.10

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: plpgsql; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS plpgsql WITH SCHEMA pg_catalog;


--
-- Name: EXTENSION plpgsql; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON EXTENSION plpgsql IS 'PL/pgSQL UUID OSSP';


--
-- Name: uuid-ossp; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS "uuid-ossp" WITH SCHEMA public;


--
-- Name: EXTENSION "uuid-ossp"; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON EXTENSION "uuid-ossp" IS 'Generate universally unique identifiers (UUIDs). See ';


SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: contenttype; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contenttype (
    id integer NOT NULL,
    machine_name uuid NOT NULL,
    label character varying(100) NOT NULL,
    schema_id character varying(255) NOT NULL,
    description character varying(1000) NOT NULL,
    required_fields text NOT NULL
);


--
-- Name: featured_result; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.featured_result (
    id integer NOT NULL,
    trigger character varying(255) NOT NULL,
    title character varying(255) NOT NULL,
    teaser character varying(255) NOT NULL,
    thumbnail character varying(255) DEFAULT NULL::character varying,
    link character varying(255) NOT NULL,
    priority integer NOT NULL
);


--
-- Name: featured_result_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.featured_result_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: fos_user; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.fos_user (
    id integer NOT NULL,
    username character varying(180) NOT NULL,
    username_canonical character varying(180) NOT NULL,
    email character varying(180) NOT NULL,
    email_canonical character varying(180) NOT NULL,
    enabled boolean NOT NULL,
    salt character varying(255) DEFAULT NULL::character varying,
    password character varying(255) NOT NULL,
    last_login timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    confirmation_token character varying(180) DEFAULT NULL::character varying,
    password_requested_at timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    roles text NOT NULL,
    display_name character varying(255) DEFAULT NULL::character varying,
    api_key character varying(255) DEFAULT NULL::character varying
);


--
-- Name: COLUMN fos_user.roles; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.fos_user.roles IS '(DC2Type:array)';


--
-- Name: fos_user_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.fos_user_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: my_shelf_folder; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.my_shelf_folder (
    id integer NOT NULL,
    owner_id integer,
    myshelftag uuid NOT NULL,
    tag_name character varying(255) DEFAULT NULL::character varying,
    is_public boolean NOT NULL,
    notes character varying(255) DEFAULT NULL::character varying,
    sort_order integer NOT NULL,
    date_added timestamp(0) without time zone NOT NULL,
    last_updated timestamp(0) without time zone NOT NULL
);


--
-- Name: my_shelf_folder_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.my_shelf_folder_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: my_shelf_record; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.my_shelf_record (
    id integer NOT NULL,
    owner_id integer,
    record_id uuid NOT NULL,
    myshelffolder uuid,
    notes character varying(255) DEFAULT NULL::character varying,
    sort_order integer NOT NULL,
    date_added timestamp(0) without time zone NOT NULL,
    last_updated timestamp(0) without time zone NOT NULL
);


--
-- Name: my_shelf_record_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.my_shelf_record_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: record; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.record (
    id integer NOT NULL,
    dapid uuid NOT NULL,
    created_date timestamp(0) without time zone NOT NULL,
    updated_date timestamp(0) without time zone NOT NULL,
    remote_system uuid NOT NULL,
    remote_id character varying(255) NOT NULL,
    record_type character varying(255) NOT NULL,
    metadata jsonb NOT NULL
);


--
-- Name: COLUMN record.metadata; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.record.metadata IS '(DC2Type:json_document)';


--
-- Name: record_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.record_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: remote_system; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.remote_system (
    id integer NOT NULL,
    dapid uuid NOT NULL,
    createddate timestamp(0) without time zone NOT NULL,
    updateddate timestamp(0) without time zone NOT NULL,
    label character varying(255) NOT NULL,
    description text NOT NULL,
    uri character varying(255) NOT NULL
);


--
-- Name: remote_system_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.remote_system_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contenttype contenttype_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contenttype
    ADD CONSTRAINT contenttype_pkey PRIMARY KEY (id);


--
-- Name: featured_result featured_result_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.featured_result
    ADD CONSTRAINT featured_result_pkey PRIMARY KEY (id);


--
-- Name: fos_user fos_user_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fos_user
    ADD CONSTRAINT fos_user_pkey PRIMARY KEY (id);


--
-- Name: my_shelf_folder my_shelf_folder_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.my_shelf_folder
    ADD CONSTRAINT my_shelf_folder_pkey PRIMARY KEY (id);


--
-- Name: my_shelf_record my_shelf_record_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.my_shelf_record
    ADD CONSTRAINT my_shelf_record_pkey PRIMARY KEY (id);


--
-- Name: record record_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.record
    ADD CONSTRAINT record_pkey PRIMARY KEY (id);


--
-- Name: remote_system remote_system_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.remote_system
    ADD CONSTRAINT remote_system_pkey PRIMARY KEY (id);


--
-- Name: idx_a9039bb87e3c61f9; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_a9039bb87e3c61f9 ON public.my_shelf_folder USING btree (owner_id);


--
-- Name: idx_de950de47e3c61f9; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_de950de47e3c61f9 ON public.my_shelf_record USING btree (owner_id);


--
-- Name: uniq_46d705e72df1af63; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX uniq_46d705e72df1af63 ON public.remote_system USING btree (dapid);


--
-- Name: uniq_957a647992fc23a8; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX uniq_957a647992fc23a8 ON public.fos_user USING btree (username_canonical);


--
-- Name: uniq_957a6479a0d96fbf; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX uniq_957a6479a0d96fbf ON public.fos_user USING btree (email_canonical);


--
-- Name: uniq_957a6479c05fb297; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX uniq_957a6479c05fb297 ON public.fos_user USING btree (confirmation_token);


--
-- Name: uniq_9b349f912df1af63; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX uniq_9b349f912df1af63 ON public.record USING btree (dapid);


--
-- Name: my_shelf_folder fk_a9039bb87e3c61f9; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.my_shelf_folder
    ADD CONSTRAINT fk_a9039bb87e3c61f9 FOREIGN KEY (owner_id) REFERENCES public.fos_user(id);


--
-- Name: my_shelf_record fk_de950de47e3c61f9; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.my_shelf_record
    ADD CONSTRAINT fk_de950de47e3c61f9 FOREIGN KEY (owner_id) REFERENCES public.fos_user(id);


--
-- PostgreSQL database dump complete
--

